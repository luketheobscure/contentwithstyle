$archive_dates = []

module Jekyll

  class ArchivePage < Page
    def initialize(site, base, path, category)
      @site = site
      @base = base
      @dir  = path
      @name = 'index.html'
      self.process(@name)

      # Read the YAML data from the layout page.
      self.read_yaml(File.join(base, '_layouts'), 'archive_index.html')
      self.data['title'] = 'Archive - ' << category
      self.data['path'] = path
      self.data['archive'] = true
      self.data['category'] = category
    end
  end
  
  class Site
    def write_archive_pages
      dates = Array.new

      for post in self.posts
        date_current = post.date.strftime('/archive/date/%Y/%B')

        if !dates.include? date_current 
          dates << date_current
          date_category = post.date.strftime('%B %Y')

          date_index = ArchivePage.new(self, self.source, date_current, date_category)
          date_index.render(self.layouts, site_payload)
          date_index.write(self.dest)
          # Record the fact that this page has been added, otherwise Site::cleanup will remove it.
          self.pages << date_index
          $archive_dates << { 
            'year' => post.date.strftime('%Y'), 
            'month' => post.date.strftime('%B'), 
            'url' => date_current,
          }
        end
        
        # p post.inspect
        # if !authors.include? post.author
        #   authors << post.author
        #   author_category = post.date.strftime('%B %Y')
        #   author_path = '/archive/author/' << post.author_id << '/' << CGI::escape(post.author)
        # 
        #   author_index = ArchivePage.new(self, self.source, author_path, author_category)
        #   author_index.render(self.layouts, site_payload)
        #   author_index.write(self.dest)
        #   # Record the fact that this page has been added, otherwise Site::cleanup will remove it.
        #   self.pages << author_index
        # end
        
      end
    end
  end
  
  
  class Archive < Generator
    safe true
    priority :low

    def generate(site)
      site.write_archive_pages
    end
  end
  
  
  class ArchiveByDate < Liquid::Tag
    def initialize(tag_name, text, tokens)
      super
      old_year = false
      old_month = false
      
      html = ''
      dates = $archive_dates.reverse
      
      for i in dates
        if i['year'] != old_year
          if old_year
            html << '</ul>'
          end
          
          html << '<h3>' << i['year'] << '</h3><ul class="clearfix">'
        end
          
        if i['month'] != old_month
          html << '<li><a href="' << i['url'] << '">' << i['month'] << '</a></li>'
        end

        old_year = i['year']  
        old_month = i['month']  
      end
      
      html << '</ul>'
      @html = html
    end

    def render(context)
      "#{@html}"
    end
  end 
end

Liquid::Template.register_tag('archive_by_date', Jekyll::ArchiveByDate)