<?php
namespace Uploader;


class AjaxRequest {
    /**
     * Save the file to the specified path
     *
     * @param $path
     *
     * @return boolean TRUE on success
     */
    public function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->get_size()) {
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }

    public function get_name() {
        return $_GET['qqfile'];
    }

    public function get_size() {
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            return (int)$_SERVER["CONTENT_LENGTH"];
        }
        else {
            throw new \Exception('Getting content length is not supported.');
        }
    }
} 