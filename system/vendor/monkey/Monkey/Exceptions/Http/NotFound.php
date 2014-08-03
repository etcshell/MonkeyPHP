<?php
/**
 * monkeyphp - NotFound.php
 *
 * PHP Version 5.4
 *
 * @author zhangming <zhangming@ec3s.com>
 * @copyright 2014 ec3s.com
 * @license Copyright (c) 2013 ec3s (http://www.ec3s.com)
 * @version GIT:<git_id>
 * @link http://www.ec3s.com
 */
namespace Monkey\Exceptions\Http;
class NotFound extends Base {

    /**
     * @var int
     */
    protected $statusCode = 404;

    public function showError($info) {
        if (self::$app->DEBUG) {
            parent::showError($info);
        }
        else {
            self::$app->errorReporting()->show404();
            exit;
        }
    }
} 