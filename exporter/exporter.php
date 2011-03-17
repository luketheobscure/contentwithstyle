<?php

require_once 'markdownify/markdownify.php';

class Exporter {
    
    const DBHOST = '127.0.0.1'; 
    const DBNAME = 'cws_blog'; 
    const DBUSER = 'root';
    const DBPASS = 'root';
    
    private $link;
    private $path;
    private $md;
        
    public function __construct() {
        $this->link = mysql_connect(self::DBHOST, self::DBUSER, self::DBPASS);

        if (!$this->link) {
            die('Could not connect: ' . mysql_error());
        }
        
        $db_selected = mysql_select_db(self::DBNAME, $this->link);
        if (!$db_selected) {
            die('Can\'t use foo : ' . mysql_error());
        }
    }    
    
    public function __destruct() {
        mysql_close($this->link);
    }
    
    public function setDir($dir) {
        $this->path = $dir;
        mkdir($this->path);
    }
    
    public function run() {
        echo "\n";
        echo "Starting ...";
        echo "\n";
        echo 'Exporting to ' . $this->path;
        echo "\n";

        $query = $this->getQuery();
        $result = mysql_query($query);

        if (!$result) {
            die('Invalid query: ' . mysql_error());
        }
        
        while ($row = mysql_fetch_assoc($result)) {
            $this->createFile($row);
        }

        mysql_free_result($result);
        echo "\n";
    }

    private function  createFile($row) {
        $this->md = new Markdownify();
        
        foreach($row as $k=>$v) {
            $row[$k] = stripslashes($v);
        };

        $str = '';
        $str .= "---\n";
        $str .= "layout : post\n";
        $str .= "permalink : /content/" . $row['url_token'] . "/index.html\n";
        $str .= "author : \"" . $row['first_name'] . " " . $row['last_name'] . "\"\n";
        $str .= "author_id : " . $row['author_id'] . "\n";
        $str .= "title : \"" . $row['headline'] . "\"\n";
        $str .= "date : \"" . $row['published'] . "\"\n";
        $str .= "dateformatted : \"" . date('F j Y, H:i', strtotime($row['published'])) . "\"\n";
        $str .= "excerpt : |\n";
        $str .=  '    ' . str_replace("\n", "\n    ", $row['excerpt']);
        $str .= "\n";
        $str .= "categories : \n";
        $str .= "   - " . date('F Y', strtotime($row['published'])) . "\n";
        $str .= "   - \"" . $row['first_name'] . " " . $row['last_name'] . "\"\n";
        $str .= "---\n\n";

        $str .= $row['text'];
        
        $dir = $this->path . '/_posts';
        mkdir($dir);
        file_put_contents($dir . '/'. date('Y-m-d', strtotime($row['published'])) . '-' . $row['url_token'] . '.html', $str);
    }
    
    private function getQuery() {
        $query = "SELECT 
                    c.*, 
                    a.first_name, 
                    a.last_name
            FROM content c
            INNER JOIN authors a ON c.author_id = a.id
            WHERE c.status_id = 4
            AND listed = 1
        ";
        
        return $query;
    }
}

if(count($argv) != 2) {
    echo "\n";
    echo "Usage: php exporter.php /path/to/dir";
    echo "\n";
}

$e = new Exporter();
$e->setDir($argv[1]);
$e->run();