<?php

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

        $posts = $this->getPosts();
        
        foreach ($posts as $row) {
            $this->createFile($row);
        }

        echo "\n";
    }

    private function  createFile($row) {
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
        $str .= "comments : |\n";
        $str .=  '    ' . str_replace("\n", "\n    ", $this->getCommentsHtml($row['id']));
        $str .= "\n";
        $str .= "---\n\n";

        $str .= $row['text'];
        
        // $str .= '<div class="comments" id="comments">';
        // $str .= "<h2>Comments</h2>";
        // 
        // if(empty($comments)) {
        //     $str .= '<p class="odd">No comments have been left.</p>';
        // } else {
        //     $str .= '<ul class="comments-list">';
        // 
        // }    
        // $str .= '</div>';
        
        $dir = $this->path . '/_posts';
        mkdir($dir);
        file_put_contents($dir . '/'. date('Y-m-d', strtotime($row['published'])) . '-' . $row['url_token'] . '.html', $str);
    }
    
    private function getPosts() {
        echo 'fetching posts';
        echo "\n";
        
        $query = $this->getQuery();
        $result = mysql_query($query);

        if (!$result) {
            die('Invalid query: ' . mysql_error());
        }
        
        $posts = array();

        while ($row = mysql_fetch_assoc($result)) {
            $posts[] = $row;
        }

        mysql_free_result($result);
        echo 'fetching posts done';
        echo "\n";

        return $posts;
    }
    
    private function getCommentsHtml($id) {
        $str = '';

        $comments = $this->getComments($id);
        $cnt = 0;
        foreach($comments as $comment) {
            $cnt++;
            $class = (($cnt % 2) == 0) ? 'even' : 'odd';

    		if(!empty($comment['twitter_screen_name'])) {
    			$class .= ' twitter';
    		}

            $str .= '<li class="' . $class . '" id="comment-' . $comment['id'] . '">';
            $str .= "\n";
            $str .= '<div class="comment-text">' . $comment['text'] . '</div>';
            $str .= "\n";
            $str .= '<p class="comment-info">by ';
            $str .= "\n";
                     
            if(!empty($comment['twitter_screen_name'])) {
                $str .= '<a href="http://www.twitter.com/' . $comment['twitter_screen_name'] . '" rel="nofollow">' . $comment['twitter_screen_name'] . '</a>';
            } elseif(!empty($comment['website']) && $comment['website'] != 'http://') {    
                $str .= '<a href="';
                $str .=  (strpos ($comment['website'], 'http://') !==0 && strpos ($comment['website'], 'https://')!==0) ?
                    'http://'.$comment['website'] : $comment['website'];
                $str .= '" rel="nofollow">' .  $comment['username'] . '</a>'; 
            } else {    
                $str .= $comment['username'];
            }
                    
            $str .= 'on ' . date('F j Y, H:i', strtotime($comment['created']));
            $str .= '<a href="#comment-' . $comment['id'] .'">#</a>';
            $str .= "\n";
            $str .= '</p>';
            $str .= "\n";
            $str .= '</li>';
            $str .= "\n";
        }
        
        return $str;
    }
    
    private function getComments($id) {
        echo 'fetching comments for '.$id;
        echo "\n";
        $query = $this->getCommentsQuery($id);
        $result = mysql_query($query);

        if (!$result) {
            die('Invalid query: ' . mysql_error());
        }
        
        $comments = array();
        
        while ($row = mysql_fetch_assoc($result)) {
            $comments[] = $row;
        }

        mysql_free_result($result);
        mysql_free_result($result);
        echo 'fetching comments for '.$id.' done';
        echo "\n";
        return $comments;
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


    private function getCommentsQuery($id) {
        $query = "SELECT 
                c.*, 
                u.name, 
                u.email,
                u.website
            FROM comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.status_id = 4
            AND c.content_id = $id
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