# Sites parser

## Description

Sites asynchronous parser. Written and supported in 2015-2017 years.

Designed to collect data from web catalogues, such as online stores, boards, markets, etc. Generates and Excel table (XML) as output.

Parser consists of 2 parts - core and client.

Core of parser must be located somewere in web, on server that supports php 5+. Core provides next functionality:

- display list of bookmarklets for supported sites (sites that have described scenarions of parsing);
- generate client source;
- take parsed data and generate Excel table of results.

Client part is javascript code, that loads directly to browser, asyncronously loading site's pages and parse dato from them.

Client part uses jquery, Q promises and parts of lodash library.

PHP-part of parser uses some core functionality of HostCMS system, becouse original task requirment was to use parser as addon for that CMS.

## How to start

1. Locate paser somewere in web, and open index page. (Of cource, you will need an hostcms installed on your domain, and you must put parser in subfolder, not to root)
2. Open url to parser in your browser. You will see an bookmarklets list. Place these bookmarklets to Favorites panel of your browser.
3. Click to some bookmarklet on favorites. The site will be open in browser. 
4. To start parsing, please, click ones more to the same bookmarklet. Now parser client code will be dowloaded from pareser's host and injected to webpage. In case of success, you will be prompted "Start parsing? Y/N". If you press Yes, parsing will be started
5. Wait for parsing finish (you may see the progress in javascript console of browser) and excel document will be downloaded to youк computer.
6. Profit!

Note! Do not close tab with parsing site!

## Scenarios

Parsing scenarios for supported sites are located in `scenario` folder

## Requirements

- Hostcms v6.х
- public domain
- server with php 5+ support
- any modern browser (I recommend to use chrome)
