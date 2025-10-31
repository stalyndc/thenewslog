<?php declare(strict_types = 1);

// odsl-/home/sd/Desktop/thenewslog/app
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/home/sd/Desktop/thenewslog/app/Services/OgExtractor.php' => 
    array (
      0 => '2892f138d413bcfd678c8844d13c600a52f8d9f1',
      1 => 
      array (
        0 => 'app\\services\\ogextractor',
      ),
      2 => 
      array (
        0 => 'app\\services\\extract',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Auth.php' => 
    array (
      0 => '983c0115a4ae32cd6afb238360904c813ffc4686',
      1 => 
      array (
        0 => 'app\\services\\auth',
      ),
      2 => 
      array (
        0 => 'app\\services\\attempt',
        1 => 'app\\services\\check',
        2 => 'app\\services\\logout',
        3 => 'app\\services\\hasvalidcredentials',
        4 => 'app\\services\\sessiontimeout',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Mailer.php' => 
    array (
      0 => 'eae7de661f3ac4fb5e9c0947e0d7529616b42287',
      1 => 
      array (
        0 => 'app\\services\\mailer',
      ),
      2 => 
      array (
        0 => 'app\\services\\send',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Csrf.php' => 
    array (
      0 => '843294a7469b86ca116b85d61aa4e235c63046f7',
      1 => 
      array (
        0 => 'app\\services\\csrf',
      ),
      2 => 
      array (
        0 => 'app\\services\\token',
        1 => 'app\\services\\validate',
        2 => 'app\\services\\assertvalid',
        3 => 'app\\services\\extracttoken',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/RateLimiter.php' => 
    array (
      0 => '6a78e615bc86b3ee41810a7a45ad09b8e6febcdc',
      1 => 
      array (
        0 => 'app\\services\\ratelimiter',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\allow',
        2 => 'app\\services\\timetoreset',
        3 => 'app\\services\\isblocked',
        4 => 'app\\services\\recordfailure',
        5 => 'app\\services\\recordsuccess',
        6 => 'app\\services\\gettimeremaining',
        7 => 'app\\services\\getattemptdata',
        8 => 'app\\services\\saveattemptdata',
        9 => 'app\\services\\clearattemptdata',
        10 => 'app\\services\\getfilepath',
        11 => 'app\\services\\getthrottlefile',
        12 => 'app\\services\\readjsonfile',
        13 => 'app\\services\\writejsonfile',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Curator.php' => 
    array (
      0 => '5918d8804bdb53f305f1b797039e36f3cd7d7f4e',
      1 => 
      array (
        0 => 'app\\services\\curator',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\curate',
        2 => 'app\\services\\createpost',
        3 => 'app\\services\\splittags',
        4 => 'app\\services\\validatetags',
        5 => 'app\\services\\resolveeditiondate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Feed/ConditionalClient.php' => 
    array (
      0 => '63e85b24d7131b4681ee8db89c9c8b22caf66a54',
      1 => 
      array (
        0 => 'app\\services\\feed\\conditionalclient',
      ),
      2 => 
      array (
        0 => 'app\\services\\feed\\setconditionalheaders',
        1 => 'app\\services\\feed\\getoptions',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/Validator.php' => 
    array (
      0 => 'ce8bf15c705b313731aff767eb790ae1f066db17',
      1 => 
      array (
        0 => 'app\\services\\validator',
      ),
      2 => 
      array (
        0 => 'app\\services\\required',
        1 => 'app\\services\\minlength',
        2 => 'app\\services\\maxlength',
        3 => 'app\\services\\email',
        4 => 'app\\services\\url',
        5 => 'app\\services\\date',
        6 => 'app\\services\\integer',
        7 => 'app\\services\\inarray',
        8 => 'app\\services\\errors',
        9 => 'app\\services\\haserrors',
        10 => 'app\\services\\firsterror',
        11 => 'app\\services\\humanize',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/HtmlSanitizer.php' => 
    array (
      0 => '64031413864a53bcc4c4ef1152c266f0a5ca0d28',
      1 => 
      array (
        0 => 'app\\services\\htmlsanitizer',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\clean',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Services/FeedFetcher.php' => 
    array (
      0 => 'faab9da35972f7c07bd0aebd0d95690817af29ad',
      1 => 
      array (
        0 => 'app\\services\\feedfetcher',
      ),
      2 => 
      array (
        0 => 'app\\services\\__construct',
        1 => 'app\\services\\fetch',
        2 => 'app\\services\\fetchfeed',
        3 => 'app\\services\\processfeedresult',
        4 => 'app\\services\\resolvemodifiedsince',
        5 => 'app\\services\\extractmetadata',
        6 => 'app\\services\\firstheader',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Bootstrap/Router.php' => 
    array (
      0 => '6056c49720073a0b0ccd0cf69eb008514409369e',
      1 => 
      array (
        0 => 'app\\bootstrap\\router',
      ),
      2 => 
      array (
        0 => 'app\\bootstrap\\__construct',
        1 => 'app\\bootstrap\\get',
        2 => 'app\\bootstrap\\post',
        3 => 'app\\bootstrap\\match',
        4 => 'app\\bootstrap\\setnotfoundhandler',
        5 => 'app\\bootstrap\\dispatch',
        6 => 'app\\bootstrap\\addroute',
        7 => 'app\\bootstrap\\invoke',
        8 => 'app\\bootstrap\\invokeclassstring',
        9 => 'app\\bootstrap\\invokeclassarray',
        10 => 'app\\bootstrap\\normalizeresponse',
        11 => 'app\\bootstrap\\compilepath',
        12 => 'app\\bootstrap\\normalizepath',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Bootstrap/Container.php' => 
    array (
      0 => 'e2103d9a0ccd39cb8421019cf437d9bf4f5e968c',
      1 => 
      array (
        0 => 'app\\bootstrap\\container',
      ),
      2 => 
      array (
        0 => 'app\\bootstrap\\bind',
        1 => 'app\\bootstrap\\singleton',
        2 => 'app\\bootstrap\\instance',
        3 => 'app\\bootstrap\\get',
        4 => 'app\\bootstrap\\call',
        5 => 'app\\bootstrap\\build',
        6 => 'app\\bootstrap\\reflectcallable',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Bootstrap/App.php' => 
    array (
      0 => 'a2b338126b71d6bce08182531a917e0ea2e2c9e0',
      1 => 
      array (
        0 => 'app\\bootstrap\\app',
      ),
      2 => 
      array (
        0 => 'app\\bootstrap\\__construct',
        1 => 'app\\bootstrap\\handle',
        2 => 'app\\bootstrap\\container',
        3 => 'app\\bootstrap\\bootstrapenvironment',
        4 => 'app\\bootstrap\\validatedependencies',
        5 => 'app\\bootstrap\\registerbindings',
        6 => 'app\\bootstrap\\createmysqlpdo',
        7 => 'app\\bootstrap\\registerroutes',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/SitemapController.php' => 
    array (
      0 => '3cb05f333c5ec56737334cb7f0164311aa508913',
      1 => 
      array (
        0 => 'app\\controllers\\sitemapcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/TagController.php' => 
    array (
      0 => '3b3db215116d89942ab1abd7c9f26721c7c0bf21',
      1 => 
      array (
        0 => 'app\\controllers\\tagcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\index',
        2 => 'app\\controllers\\show',
        3 => 'app\\controllers\\baseurl',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/PageController.php' => 
    array (
      0 => '146d36d958c4de39f80b50853b22dc19e587eee2',
      1 => 
      array (
        0 => 'app\\controllers\\pagecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\about',
        2 => 'app\\controllers\\privacy',
        3 => 'app\\controllers\\terms',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/HealthController.php' => 
    array (
      0 => '777ee3b87c618601e119b21a4842213de564ba2c',
      1 => 
      array (
        0 => 'app\\controllers\\healthcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\__invoke',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/HomeController.php' => 
    array (
      0 => '1b7e78cf788f6a64e8550b8fdd17cb884b8b04e3',
      1 => 
      array (
        0 => 'app\\controllers\\homecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\__invoke',
        2 => 'app\\controllers\\parseeditiondate',
        3 => 'app\\controllers\\baseurl',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/RssController.php' => 
    array (
      0 => '76798ee38dcd330495a13c679e9bb603336700c5',
      1 => 
      array (
        0 => 'app\\controllers\\rsscontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\daily',
        2 => 'app\\controllers\\buildfeed',
        3 => 'app\\controllers\\baseurl',
        4 => 'app\\controllers\\formatrssdate',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/ErrorController.php' => 
    array (
      0 => '1a5a9494c2d4235755d87ab312a47101fde14416',
      1 => 
      array (
        0 => 'app\\controllers\\errorcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\notfound',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/BaseController.php' => 
    array (
      0 => '986eccf120eff7eceb53c7e11ab4279710645489',
      1 => 
      array (
        0 => 'app\\controllers\\basecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\render',
        2 => 'app\\controllers\\errorresponse',
        3 => 'app\\controllers\\notfound',
        4 => 'app\\controllers\\unauthorized',
        5 => 'app\\controllers\\servererror',
        6 => 'app\\controllers\\log',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/EditionArchiveController.php' => 
    array (
      0 => '9fe700a02bcdd1d06253ca4c69f282ef0284aa8f',
      1 => 
      array (
        0 => 'app\\controllers\\editionarchivecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\__construct',
        1 => 'app\\controllers\\index',
        2 => 'app\\controllers\\show',
        3 => 'app\\controllers\\partial',
        4 => 'app\\controllers\\baseurl',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/TagController.php' => 
    array (
      0 => 'e61a02049750d69b234544aa8e9b055a1651f900',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\tagcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\suggest',
        2 => 'app\\controllers\\admin\\validate',
        3 => 'app\\controllers\\admin\\all',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/InboxController.php' => 
    array (
      0 => '141a66744ab6f5c0f3bb59feb30b8145ec9fec12',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\inboxcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\index',
        2 => 'app\\controllers\\admin\\partial',
        3 => 'app\\controllers\\admin\\poll',
        4 => 'app\\controllers\\admin\\delete',
        5 => 'app\\controllers\\admin\\ignore',
        6 => 'app\\controllers\\admin\\buildcontext',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/PostController.php' => 
    array (
      0 => '56d37107ad4b1c9274f6691034c513bd9cf15e94',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\postcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\create',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/AdminController.php' => 
    array (
      0 => '52708ca0f064c195d6c7b8f598514d08ad279c97',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\admincontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\withadminmetrics',
        2 => 'app\\controllers\\admin\\formatrelative',
        3 => 'app\\controllers\\admin\\guardcsrf',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/CurateController.php' => 
    array (
      0 => 'ce53e33de257886009827782284cefce15aecd26',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\curatecontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\show',
        2 => 'app\\controllers\\admin\\store',
        3 => 'app\\controllers\\admin\\destroy',
        4 => 'app\\controllers\\admin\\resolvecuratedfromitem',
        5 => 'app\\controllers\\admin\\buildformstate',
        6 => 'app\\controllers\\admin\\safefinditem',
        7 => 'app\\controllers\\admin\\trimornull',
        8 => 'app\\controllers\\admin\\tagstostring',
        9 => 'app\\controllers\\admin\\sanitizeitem',
        10 => 'app\\controllers\\admin\\sanitizecurated',
        11 => 'app\\controllers\\admin\\sanitizeedition',
        12 => 'app\\controllers\\admin\\sanitizetags',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/EditionController.php' => 
    array (
      0 => 'a8d120627ba6f78122ae44df64c39abe49a4591a',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\editioncontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\show',
        2 => 'app\\controllers\\admin\\reorder',
        3 => 'app\\controllers\\admin\\parsescheduledfor',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/AuthController.php' => 
    array (
      0 => '003b9d306ae9d99ea972a8ffdfb8ba3b99cdf355',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\authcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\login',
        2 => 'app\\controllers\\admin\\getclientip',
        3 => 'app\\controllers\\admin\\logout',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Controllers/Admin/FeedController.php' => 
    array (
      0 => '678b7814b587a5e720e361900de10863aa9989a8',
      1 => 
      array (
        0 => 'app\\controllers\\admin\\feedcontroller',
      ),
      2 => 
      array (
        0 => 'app\\controllers\\admin\\__construct',
        1 => 'app\\controllers\\admin\\index',
        2 => 'app\\controllers\\admin\\store',
        3 => 'app\\controllers\\admin\\update',
        4 => 'app\\controllers\\admin\\destroy',
        5 => 'app\\controllers\\admin\\refresh',
        6 => 'app\\controllers\\admin\\buildcontext',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/ItemRepository.php' => 
    array (
      0 => '797e96f6d0c857bacb1ec0bfe4dd155a4a49c99a',
      1 => 
      array (
        0 => 'app\\repositories\\itemrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\inbox',
        1 => 'app\\repositories\\inboxafter',
        2 => 'app\\repositories\\find',
        3 => 'app\\repositories\\findbyhash',
        4 => 'app\\repositories\\create',
        5 => 'app\\repositories\\updatestatus',
        6 => 'app\\repositories\\markcurated',
        7 => 'app\\repositories\\countnew',
        8 => 'app\\repositories\\countbystatus',
        9 => 'app\\repositories\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/TagRepository.php' => 
    array (
      0 => 'e0131561a8ffc7b3eb3e6761c646070173b5428c',
      1 => 
      array (
        0 => 'app\\repositories\\tagrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\allwithcounts',
        1 => 'app\\repositories\\findbyslug',
        2 => 'app\\repositories\\ensure',
        3 => 'app\\repositories\\search',
        4 => 'app\\repositories\\tagsforids',
        5 => 'app\\repositories\\tagsforcuratedlinks',
        6 => 'app\\repositories\\syncforcuratedlink',
        7 => 'app\\repositories\\deleteorphans',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/FeedRepository.php' => 
    array (
      0 => '11d5ca1e54f4c7674d612fbeb9856ce7ce96753d',
      1 => 
      array (
        0 => 'app\\repositories\\feedrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\all',
        1 => 'app\\repositories\\active',
        2 => 'app\\repositories\\find',
        3 => 'app\\repositories\\findbyfeedurl',
        4 => 'app\\repositories\\create',
        5 => 'app\\repositories\\update',
        6 => 'app\\repositories\\ensure',
        7 => 'app\\repositories\\touchchecked',
        8 => 'app\\repositories\\ismissingheadercolumns',
        9 => 'app\\repositories\\incrementfailcount',
        10 => 'app\\repositories\\resetfailcount',
        11 => 'app\\repositories\\infersiteurl',
        12 => 'app\\repositories\\latestfetchtime',
        13 => 'app\\repositories\\failingcount',
        14 => 'app\\repositories\\countall',
        15 => 'app\\repositories\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/CuratedLinkRepository.php' => 
    array (
      0 => '165410e123098c0db7e8237d829cee1ac5548188',
      1 => 
      array (
        0 => 'app\\repositories\\curatedlinkrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\latestpublished',
        1 => 'app\\repositories\\foreditiondate',
        2 => 'app\\repositories\\find',
        3 => 'app\\repositories\\findbyitem',
        4 => 'app\\repositories\\publishedforeditiondate',
        5 => 'app\\repositories\\latestpublishededition',
        6 => 'app\\repositories\\create',
        7 => 'app\\repositories\\attachtoedition',
        8 => 'app\\repositories\\detachfromeditions',
        9 => 'app\\repositories\\updateeditionpositions',
        10 => 'app\\repositories\\update',
        11 => 'app\\repositories\\nextpositionforedition',
        12 => 'app\\repositories\\pivotforcuratedlink',
        13 => 'app\\repositories\\detachfromedition',
        14 => 'app\\repositories\\positionafterpinned',
        15 => 'app\\repositories\\attachtoeditionatposition',
        16 => 'app\\repositories\\attachtoeditionattop',
        17 => 'app\\repositories\\movetotopofedition',
        18 => 'app\\repositories\\setpinned',
        19 => 'app\\repositories\\publishallforedition',
        20 => 'app\\repositories\\streamcountfortag',
        21 => 'app\\repositories\\streamfortag',
        22 => 'app\\repositories\\delete',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/BaseRepository.php' => 
    array (
      0 => '4bd38baf40558916c619d550ddd7d952aa2c1cf2',
      1 => 
      array (
        0 => 'app\\repositories\\baserepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\__construct',
        1 => 'app\\repositories\\setconnection',
        2 => 'app\\repositories\\begintransaction',
        3 => 'app\\repositories\\commit',
        4 => 'app\\repositories\\rollback',
        5 => 'app\\repositories\\fetchall',
        6 => 'app\\repositories\\fetch',
        7 => 'app\\repositories\\execute',
        8 => 'app\\repositories\\insert',
        9 => 'app\\repositories\\prepare',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Repositories/EditionRepository.php' => 
    array (
      0 => '0b4c5f3d0f0e8aa5257db545137349ba15ea23df',
      1 => 
      array (
        0 => 'app\\repositories\\editionrepository',
      ),
      2 => 
      array (
        0 => 'app\\repositories\\find',
        1 => 'app\\repositories\\findbydate',
        2 => 'app\\repositories\\findbyslug',
        3 => 'app\\repositories\\create',
        4 => 'app\\repositories\\ensurefordate',
        5 => 'app\\repositories\\findbycuratedlink',
        6 => 'app\\repositories\\updatestatus',
        7 => 'app\\repositories\\publishedwithcounts',
        8 => 'app\\repositories\\countpublished',
        9 => 'app\\repositories\\findpublishedbydate',
        10 => 'app\\repositories\\dueforpublication',
        11 => 'app\\repositories\\clearschedule',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Http/Response.php' => 
    array (
      0 => '76bbdfc2d224a89e415af3b01498b6ea32128cd3',
      1 => 
      array (
        0 => 'app\\http\\response',
      ),
      2 => 
      array (
        0 => 'app\\http\\__construct',
        1 => 'app\\http\\setheader',
        2 => 'app\\http\\status',
        3 => 'app\\http\\headers',
        4 => 'app\\http\\content',
        5 => 'app\\http\\send',
        6 => 'app\\http\\applysecurityheaders',
        7 => 'app\\http\\json',
        8 => 'app\\http\\redirect',
        9 => 'app\\http\\cached',
        10 => 'app\\http\\setcsp',
        11 => 'app\\http\\setcspreportonly',
        12 => 'app\\http\\cspnonce',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Http/Request.php' => 
    array (
      0 => '89d262db623f8274b108d0292d476794453aef60',
      1 => 
      array (
        0 => 'app\\http\\request',
      ),
      2 => 
      array (
        0 => 'app\\http\\__construct',
        1 => 'app\\http\\fromglobals',
        2 => 'app\\http\\parsejsonbody',
        3 => 'app\\http\\method',
        4 => 'app\\http\\path',
        5 => 'app\\http\\all',
        6 => 'app\\http\\query',
        7 => 'app\\http\\input',
        8 => 'app\\http\\json',
        9 => 'app\\http\\inputint',
        10 => 'app\\http\\inputbool',
        11 => 'app\\http\\ispost',
        12 => 'app\\http\\server',
        13 => 'app\\http\\header',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Models/Item.php' => 
    array (
      0 => '509aaad9a8027831df7b3304f09541dffb6c276a',
      1 => 
      array (
        0 => 'app\\models\\item',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Models/Subscriber.php' => 
    array (
      0 => 'fb31e5bfe55e7c6787051456666152be0509e159',
      1 => 
      array (
        0 => 'app\\models\\subscriber',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Models/Edition.php' => 
    array (
      0 => 'bff2ffe7ca4bd2ee7e9de3fb76803063efb44151',
      1 => 
      array (
        0 => 'app\\models\\edition',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Models/Feed.php' => 
    array (
      0 => '182dd705194187273ee7b02979d5baa3a43a6071',
      1 => 
      array (
        0 => 'app\\models\\feed',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Models/CuratedLink.php' => 
    array (
      0 => '1629b1992042842e8e71c95f9bf1df09c073fb05',
      1 => 
      array (
        0 => 'app\\models\\curatedlink',
      ),
      2 => 
      array (
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Helpers/Str.php' => 
    array (
      0 => '4ee5c9dd0f85028c105723d24bdfa7bf2ad1541f',
      1 => 
      array (
        0 => 'app\\helpers\\str',
      ),
      2 => 
      array (
        0 => 'app\\helpers\\slug',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Helpers/Encoding.php' => 
    array (
      0 => 'f156d7c2a9a448c301176ee657a359936a7d64f5',
      1 => 
      array (
        0 => 'app\\helpers\\encoding',
      ),
      2 => 
      array (
        0 => 'app\\helpers\\decodehtmlentities',
        1 => 'app\\helpers\\ensureutf8',
        2 => 'app\\helpers\\isutf8',
        3 => 'app\\helpers\\mbconvert',
        4 => 'app\\helpers\\iconvconvert',
        5 => 'app\\helpers\\stripnonascii',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Helpers/Url.php' => 
    array (
      0 => '40718cd577e04dc3b01f50de0a6702b8bb51d16e',
      1 => 
      array (
        0 => 'app\\helpers\\url',
      ),
      2 => 
      array (
        0 => 'app\\helpers\\normalize',
        1 => 'app\\helpers\\isvalid',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Helpers/ResponseBuilder.php' => 
    array (
      0 => 'e1cdc39c8343b25f298f91c544b3216e7ac3d9b6',
      1 => 
      array (
        0 => 'app\\helpers\\responsebuilder',
      ),
      2 => 
      array (
        0 => 'app\\helpers\\json',
        1 => 'app\\helpers\\success',
        2 => 'app\\helpers\\error',
        3 => 'app\\helpers\\validationerror',
        4 => 'app\\helpers\\notfound',
        5 => 'app\\helpers\\unauthorized',
        6 => 'app\\helpers\\forbidden',
        7 => 'app\\helpers\\toomanyrequests',
        8 => 'app\\helpers\\redirectwithmessage',
      ),
      3 => 
      array (
      ),
    ),
    '/home/sd/Desktop/thenewslog/app/Helpers/Html.php' => 
    array (
      0 => '021a09c09a1d5ecf063dc83e21c77034480735e6',
      1 => 
      array (
        0 => 'app\\helpers\\html',
      ),
      2 => 
      array (
        0 => 'app\\helpers\\escape',
        1 => 'app\\helpers\\truncate',
        2 => 'app\\helpers\\texttohtml',
        3 => 'app\\helpers\\slug',
        4 => 'app\\helpers\\attributes',
        5 => 'app\\helpers\\escapejs',
        6 => 'app\\helpers\\escapecss',
      ),
      3 => 
      array (
      ),
    ),
  ),
));