<?php
namespace Uploader;


class FormRequest {
    /**
     * Save the file to the specified path
     *
     * @param $path
     *
     * @return boolean TRUE on success
     */
    public function save($path) {
        if (!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)) {
            return false;
        }
        return true;
    }

    public function get_name() {
        return $_FILES['qqfile']['name'];
    }

    public function get_size() {
        return $_FILES['qqfile']['size'];
    }
} 