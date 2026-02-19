<?php

return [
    // About Sitemaps
    'about_sitemaps' => [
        'page_title' => 'About Sitemaps',
        'title' => 'About Sitemaps',
        'back_home' => 'HOME',
        'section1_title' => 'Search Engine Giants Adopting the XML Protocol',
        'p1' => 'If you own or maintain a website or intend to own one, wouldn\'t it be great if you get frequent visitors who find satisfaction in getting exactly the information they need from your page?',
        'p2' => 'Back in 2005, the search engine Google launched the Sitemap 0.84 Protocol, designed to use the XML format. A sitemap is a way of organizing a website, identifying the URLs and the data under each section. Previously, the sitemaps were primarily geared for the users of the website. However, Google\'s XML format was designed for the search engines, allowing them to find the data faster and more efficiently.',
        'p3' => 'Google\'s new sitemap protocol was developed in response to the increasing size and complexity of websites. Business websites often contained hundreds of products in their catalogues; while the popularity of blogging led to webmasters updating their material at least once a day, not to mention popular community-building tools like forums and message boards. As websites got bigger and bigger, it was difficult for search engines to keep track of all this material, sometimes \'skipping\' information as it crawled through these rapidly changing pages.',
        'p4' => 'Through the XML protocol, search engines could track the URLs more efficiently, optimizing their search by placing all the information in one page. XML also summarizes how frequently a particular website is updated, and records the last time any changes were made.',
        'p5' => 'XML sitemaps were not, as some people thought, a tool for search engine optimization. It does not affect ranking, but it does allow search engines to make more accurate rankings and searches. It does this by providing the data that a search engine needs, and putting it one place — quite handy, given that there are millions of websites to plough through.',
        'p6' => 'To encourage other search engines to adopt the XML protocol, Google published it under the Attribution/Share Alike Creative Commons license. Its efforts paid off. Recently, Google happily announced that Yahoo and Microsoft had agreed to \'officially support\' the XML protocol which has now been updated to the Sitemap 0.9 protocol and jointly sponsored www.sitemaps.org, a site setup to explain the protocol.',
        'p7' => 'The shared recognition of the XML protocol means that website developers no longer need to create different types of sitemaps for the different search engines. They can create one file for submission, and then update it when they have made changes on the site. This simplifies the whole process of fine-tuning and expanding a website. Through this move, the XML format will soon become a standard feature of all website creation and development.',
        'related_title' => 'Related Articles',
        'related_broken_links' => 'Broken Links',
        'related_image' => 'Image Sitemap Explained',
        'related_video' => 'Video Sitemap Explained',
        'related_news' => 'News Sitemap Explained',
        'related_html' => 'HTML Sitemap Explained',
        'related_rss' => 'RSS Feed Explained',
        'related_text' => 'Text Sitemap Explained',
        'related_mobile' => 'Mobile Sitemap Explained',
        'related_about' => 'About Sitemaps',
    ],

    // Broken Links
    'broken_links' => [
        'page_title' => 'Broken Links',
        'title' => 'Broken Links',
        'section_title' => 'Find and Fix Broken Links',
        'p1' => 'Link management is a very important and absolutely essential part of maintaining a website. Broken links can stop search engine robots dead in its tracks, effectively preventing it from completely indexing a website.',
        'p2' => 'You are visiting a website that you have just discovered. The information contained in the pages are just what you need for a paper you are writing. While reading a page you come across a note that very important information is contained in another page. You click on the page, but nothing! The link is dead and there is no other way for you to get on to the page that you need! The frustration mounts up so much that you decide to go out of the website and vow never to go back to that frustrating website.',
        'p3' => 'This is the kind of scenario that would usually revolve around a website that has been lax in its management and where broken links have become rampant. Among the many bad habits or mistakes that a website can commit, having broken links is one of the most serious of them. Broken links bring with it so many negative effects that it may leave your online business or website reeling from its effects, which are very hard to correct.',
        'p4' => 'Search engine bots are stopped dead by broken links because they would think that it is the end of the line. And as previously illustrated, visitors will be turned off by a website that has dead links because they will think that there is no information available when in fact, the data is there but it becomes inaccessible because of an error in code.',
        'p5' => 'A website that is filled with broken links suffers a lot in terms of damaged reputation. The website will be seen as unprofessional and many may even think that it is a shady operation. The same goes with website owner, who may be seen as a person with a dubious reputation.',
        'p6' => 'Webmasters and website owners should take it upon themselves to \'clean house\' diligently and regularly. All of the links should be actively checked if there are any dead or broken hyperlinks. It is just part of regular housekeeping.',
        'p7' => 'Fortunately, there are companies that also realize that checking for broken links can be quite a tedious task and have devised different methods in order to make this task easier to do and consume less time. For example, our sitemap generator has programmed a standalone script that not only creates sitemaps but also looks for broken links in a website and then informs webmasters what links they are and to which pages the links are associated with.',
    ],

    // Images Sitemap
    'images_sitemap' => [
        'page_title' => 'Image Sitemap Explained',
        'title' => 'Image Sitemap Explained',
        'section_title' => 'How to create and use Image Sitemaps',
        'p1' => 'Through Google Image Sitemaps, Google receives metadata regarding the images contained on a website. Visitors can do an image search on Google. Using Google image extensions in Sitemaps provides the search engine with additional information regarding the images on the website. This can help Google discover images it may not find through crawling, such as those accessed via JavaScript forms.',
        'p2' => 'To provide Google with information regarding images on the website, the site owner must add relevant details to the standard Sitemap. This includes the type of image, subject matter, caption, title, geographic location, and license. The process enables site owners to identify which images on each page are most important.',
        'p3' => 'NOTE: :app_name will include unique images URLs in sitemap to avoid duplicating repeating images for all pages. Image sitemap is stored in a separate xml file.',
    ],

    // Video Sitemap
    'video_sitemap' => [
        'page_title' => 'Video Sitemap Explained',
        'title' => 'Video Sitemap Explained',
        'section_title' => 'How to create Video Sitemaps and how are they used',
        'p1' => 'Google Video Sitemaps provide Google with metadata about video content on a website. With a Video Sitemap, site owners can inform Google of the category, title, description, running time, and intended audience for each video contained on the site. This helps Google know about all the rich video content on the site, which should improve the listing of the site on video search results.',
        'p2' => 'When supported format video information is either submitted as a separate Sitemap or included in the regular Sitemap, the included URLs for the videos are made searchable through Google Video. The videos may also be accessed through other search products offered by Google.',
        'p3' => 'NOTE: :app_name automatically detects embedded videos on your pages and creates a separate sitemap with related details. In the current version of the standalone sitemap generator the following video sources are supported: YouTube, Google Video, Vimeo, Dailymotion, MTV, Blip.tv. In other cases, VideoObject Schema markup is supported.',
    ],

    // News Sitemap
    'news_sitemap' => [
        'page_title' => 'News Sitemap Explained',
        'title' => 'News Sitemap Explained',
        'section_title' => 'How to create and use News Sitemaps',
        'p1' => 'Google News Sitemaps provide the search engine Google with metadata regarding the specific news content on a website. This Sitemap allows the site owner to control which content is submitted to Google News. Using the News Sitemap, Google News can quickly find the news articles contained on a site.',
        'p2' => 'These Sitemaps identify the title and publication date of every article. Using genres and access tags, they also specify the type of content contained in the article. Article content is further identified using descriptions like stock tickers or relevant keywords.',
        'p3' => 'NOTE: To be able to use news sitemaps for your website you must request inclusion in Google News first, otherwise news sitemap will not be processed. :app_name tracks the changes on your site automatically and creates news sitemap in a separate file, including only new URLs for the last days.',
    ],

    // HTML Sitemap
    'html_sitemap' => [
        'page_title' => 'HTML Sitemap Explained',
        'title' => 'HTML Sitemap Explained',
        'section_title' => 'How to create and use HTML Sitemaps and how would that help your website',
        'p1' => 'An HTML sitemap allows site visitors to easily navigate a website. It is a bulleted outline text version of the site navigation. The anchor text displayed in the outline is linked to the page it references. Site visitors can go to the Sitemap to locate a topic they are unable to find by searching the site or navigating through the site menus.',
        'p2' => 'This Sitemap can also be created in XML format and submitted to search engines so they can crawl the website in a more effective manner. Using the Sitemap, search engines become aware of every page on the site, including any URLs that are not discovered through the normal crawling process used by the engine.',
        'p3' => 'Sitemaps are helpful if a site has dynamic content, is new and does not have many links to it, or contains a lot of archived content that is not well-linked.',
    ],

    // RSS Feed
    'rss_feed' => [
        'page_title' => 'RSS Feed Explained',
        'title' => 'RSS Feed Explained',
        'section_title' => 'How an RSS feed can be used?',
        'p1' => 'RSS feed allows your website visitors to track changes on your site, using one of many \'feed readers\', hence increasing the number of visits on your site.',
    ],

    // Text Sitemap
    'text_sitemap' => [
        'page_title' => 'Text Sitemap Explained',
        'title' => 'Text Sitemap Explained',
        'section_title' => 'How a text formatted sitemap can be used?',
        'p1' => 'In earlier versions of sitemap generator text-formatted sitemap was used to submit your pages to Yahoo search engine. Later though Yahoo announced support of XML sitemaps, and it\'s not used for search engines anymore.',
        'p2' => 'We have kept the option to create text sitemap in sitemap generator though since it might be useful to have a plain list of all your website links for reference in one place, a single text file.',
    ],

    // Mobile Sitemap
    'mobile_sitemap' => [
        'page_title' => 'Mobile Sitemap Explained',
        'title' => 'Mobile Sitemap Explained',
        'section_title' => 'How to create and use mobile sitemaps',
        'p1' => 'In case if your site has a specially formatted version designed for mobile devices, it is strongly recommended to create a separate mobile sitemap and submit it to search engines. That will allow search engines to better serve search requests from mobile devices and lead them to your website pages.',
        'p2' => 'NOTE: Mobile sitemap should only contain links to pages with mobile web content, otherwise regular xml sitemap can be used. Standalone sitemap generator will limit mobile sitemap to first 50,000 pages.',
    ],
];
