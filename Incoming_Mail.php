<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 


/**
 * iWAT LLC Incoming Email Class
 *
 * Used this as a resouce for building my class.
 * https://github.com/barbushin/php-imap/blob/master/src/ImapMailbox.php
 * https://github.com/barbushin/php-imap/blob/master/example/index.php
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Libraries
 * @author      iWAT LLC
 * @link        http://example.com
 */

class Incoming_Mail {
    
    public $id;
    public $date;
    public $subject;
    public $from_name;
    public $from_address;
    public $to = array();
    public $to_string;
    public $cc = array();
    public $reply_to = array();
    public $text_plain;
    public $text_html;
    protected $_attachments = array();
    
    // --------------------------------------------------------------------
    
    /**
     * Add attachements to attachments array
     * 
     * @return nothing
     */
    public function add_attachment(Incoming_Mail_Attachment $attachment) {
        $this->_attachments[$attachment->id] = $attachment;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get attachment array for message.
     *
     * @return IncomingMailAttachment[]
     */
    public function get_attachments() {
        return $this->_attachments;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get array of internal HTML links placeholders
     * 
     * @return array attachmentId => link placeholder
     */
    public function get_internal_links_placeholders() {
        return preg_match_all('/=["\'](ci?d:(\w+))["\']/i', $this->text_html, $matches) ? array_combine($matches[2], $matches[1]) : array();
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get array of internal HTML links placeholders
     * 
     * @return array attachmentId => link placeholder
     */
    public function replace_internal_links($base_uri) {
        $base_uri = rtrim($base_uri, '\\/') . '/';
        $fetched_html = $this->text_html;
        foreach($this->getInternalLinksPlaceholders() as $attachment_id => $place_holder) {
            $fetched_html = str_replace($place_holder, $base_uri . basename($this->_attachments[$attachment_id]->file_path), $fetched_html);
        }
        return $fetched_html;
    }
}


/* End of file MY_Email.php */
/* Location: ./system/application/libraries/MY_Email.php */