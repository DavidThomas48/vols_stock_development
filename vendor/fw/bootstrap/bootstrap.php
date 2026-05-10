<?php
        // load the stdlibrary - available for access as a static class
        require FW_DIR.sprintf('library%sStdLib.php',DS);
        // instantiate the autoloader
        require_once sprintf('%sautoload%sAutoLoader.php',FW_DIR,DS);
        $loader = new AutoLoader();
        // register the autoloader
        $loader->register();
        // register the base directories for the namespace prefix 
        $loader->addNamespace('app',sprintf('%sapp',ROOT_DIR));
        $loader->addNamespace('vendor',sprintf('%svendor',ROOT_DIR));
        $loader->addNamespace('fw',sprintf('%svendor%sfw',ROOT_DIR,DS));
        $loader->addNamespace('apptable',sprintf('%sapp%sdatabase%stable',ROOT_DIR,DS,DS));
        $loader->addNamespace('shared',sprintf('%sapp%sshared', ROOT_DIR,DS));
        $loader->addNamespace('database',sprintf('%sapp%sdatabase', ROOT_DIR,DS,DS));
        $loader->addNamespace('database',sprintf('%svendor%sfw%sdatabase', ROOT_DIR,DS,DS));
        $loader->addNamespace('lib',sprintf('%svendor%sfw%slibrary',ROOT_DIR,DS,DS));
        $loader->addNamespace('PHPMailer',sprintf('%svendor%sPHPMailer%ssrc',ROOT_DIR,DS,DS));
        $loader->addNamespace('Mailjet',sprintf('%svendor%smailjet%ssrc%sMailjet',ROOT_DIR,DS,DS,DS));
        $loader->addNamespace('GuzzleHttp',sprintf('%svendor%smailjet%svendor%sguzzlehttp%sguzzle%ssrc',ROOT_DIR,DS,DS,DS,DS,DS));
        $loader->addNamespace('GuzzleHttp\Psr7',sprintf('%svendor%smailjet%svendor%sguzzlehttp%spsr7%ssrc',ROOT_DIR,DS,DS,DS,DS,DS));
        $loader->addNamespace('GuzzleHttp\Promise',sprintf('%svendor%smailjet%svendor%sguzzlehttp%spromises%ssrc',ROOT_DIR,DS,DS,DS,DS,DS));
        $loader->addNamespace('Psr',sprintf('%svendor%smailjet%svendor%spsr',ROOT_DIR,DS,DS,DS));
        $loader->addNamespace('MailerSend',sprintf('%svendor%smailersend%ssrc',ROOT_DIR,DS,DS,DS));
