<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 


/**
 * iWAT LLC Email Class
 *
 * Overide of CI Email class adding the features to recieve
 * email.
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
 * 
 * 1-13-2014 - Update for php-imap fix #46
 * https://github.com/georaldc/php-imap/commit/04b0c19130a243d51269f008b52a735cfad1a82f
 * 
 */


class MY_Email extends CI_Email {
    
    var $imap_host      = "";       // IMAP Server.  Example: mail.earthlink.net
    var $imap_user      = "";       // IMAP User.
    var $imap_pass      = "";       // IMAP Password.
    var $imap_port      = "";       // IMAP Port. Example: 993 for secure connection.
    var $imap_mailbox   = "";       // IMAP Mailbox.  Example: INBOX
    var $imap_path      = "";       // IMAP Mailbox Path. Example: 'imap/ssl'
    var $imap_server_encoding   = "";   // IMAP server encoding
    var $imap_attachemnt_dir    = "";   // IMAP Attachemts directory
    

    /**
     * Constructor - Sets IMAP Email Preferences
     *
     * The constructor can be passed an array of config values
     */
    public function __construct($config = array())
    {
        
        parent::__construct($config);
    }
    
    
    // --------------------------------------------------------------------
    
    /**
     * Get IMAP mailbox connection stream
     * @param bool $forceConnection Initialize connection if it's not initialized
     * @return null|resource
     */
    public function get_imap_stream($force_connection = true) {
        static $imap_stream;
        if($force_connection) {
            if($imap_stream && (!is_resource($imap_stream) || !imap_ping($imap_stream))) {
                $this->disconnect();
                $imap_stream = null;
            }
            if(!$imap_stream) {
                $imap_stream = $this->_init_imap_stream();
            }
        }
        return $imap_stream;
    }
    
    // --------------------------------------------------------------------
    
    protected function _init_imap_stream() {
        $imap_stream = @imap_open($this->_full_imap_path(), $this->imap_user, $this->imap_pass);
        if(!$imap_stream) {
            throw new Exception('Connection error: ' . imap_last_error());
        }
        return $imap_stream;
    }
    
    // --------------------------------------------------------------------
    
    
    protected function _full_imap_path() {
        $full_imap_path = "{".$this->imap_host.":".$this->imap_port.$this->imap_path."}".$this->imap_mailbox;
        return $full_imap_path;
    }
    
    /**
     * Get information about the current mailbox.
     *
     * Returns the information in an object with following properties:
     *  Date - current system time formatted according to RFC2822
     *  Driver - protocol used to access this mailbox: POP3, IMAP, NNTP
     *  Mailbox - the mailbox name
     *  Nmsgs - number of mails in the mailbox
     *  Recent - number of recent mails in the mailbox
     *
     * @return stdClass
     */
    public function check_mailbox() {
        return imap_check($this->get_imap_stream());
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get information about the current mailbox.
     *
     * Returns an object with following properties:
     *  Date - last change (current datetime)
     *  Driver - driver
     *  Mailbox - name of the mailbox
     *  Nmsgs - number of messages
     *  Recent - number of recent messages
     *  Unread - number of unread messages
     *  Deleted - number of deleted messages
     *  Size - mailbox size
     *
     * @return object Object with info | FALSE on failure
     */
    public function get_mailbox_info() {
        return imap_mailboxmsginfo($this->get_imap_stream());
    }
    
    // --------------------------------------------------------------------
   
    /**
     * Get number of messages from the Mailbox.
     *
     * @return integer
     */
    public function get_num_msg() {
        return imap_num_msg($this->get_imap_stream());
    }
    
    // --------------------------------------------------------------------
    
    
    /**
     * Gets status information about the given mailbox.
     *
     * This function returns an object containing status information.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @return stdClass | FALSE if the box doesn't exist
     */
    public function status_mailbox() {
        return imap_status($this->get_imap_stream(), $this->imap_path, SA_ALL);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Close IMAP Connection
     *
     */
    protected function _disconnect() {
        $imap_stream = $this->get_imap_stream(false);
        if($imap_stream && is_resource($imap_stream)) {
            imap_close($imap_stream, CL_EXPUNGE);
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Marks mails listed in mailId for deletion.
     * @return bool
     */
    public function delete_mail($mail_id) {
        return imap_delete($this->get_imap_stream(), $mail_id, FT_UID);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Deletes all the mails marked for deletion by imap_delete(), imap_mail_move(), or imap_setflag_full().
     * @return bool
     */
    public function expunge_deleted_mails() {
        return imap_expunge($this->get_imap_stream());
    }
    
    // --------------------------------------------------------------------
    
    /**
     * This function performs a search on the mailbox currently opened in the given IMAP stream.
     * For example, to match all unanswered mails sent by Mom, you'd use: "UNANSWERED FROM mom".
     * Searches appear to be case insensitive. This list of criteria is from a reading of the UW
     * c-client source code and may be incomplete or inaccurate (see also RFC2060, section 6.4.4).
     *
     * @param string $criteria String, delimited by spaces, in which the following keywords are allowed. Any multi-word arguments (e.g. FROM "joey smith") must be quoted. Results will match all criteria entries.
     *    ALL - return all mails matching the rest of the criteria
     *    ANSWERED - match mails with the \\ANSWERED flag set
     *    BCC "string" - match mails with "string" in the Bcc: field
     *    BEFORE "date" - match mails with Date: before "date"
     *    BODY "string" - match mails with "string" in the body of the mail
     *    CC "string" - match mails with "string" in the Cc: field
     *    DELETED - match deleted mails
     *    FLAGGED - match mails with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     *    FROM "string" - match mails with "string" in the From: field
     *    KEYWORD "string" - match mails with "string" as a keyword
     *    NEW - match new mails
     *    OLD - match old mails
     *    ON "date" - match mails with Date: matching "date"
     *    RECENT - match mails with the \\RECENT flag set
     *    SEEN - match mails that have been read (the \\SEEN flag is set)
     *    SINCE "date" - match mails with Date: after "date"
     *    SUBJECT "string" - match mails with "string" in the Subject:
     *    TEXT "string" - match mails with text "string"
     *    TO "string" - match mails with "string" in the To:
     *    UNANSWERED - match mails that have not been answered
     *    UNDELETED - match mails that are not deleted
     *    UNFLAGGED - match mails that are not flagged
     *    UNKEYWORD "string" - match mails that do not have the keyword "string"
     *    UNSEEN - match mails which have not been read yet
     *
     * @return array Mails ids
     */
     public function search_mailbox($criteria = 'ALL') {
        $mails_ids = imap_search($this->get_imap_stream(), $criteria, SE_UID, $this->imap_server_encoding);
        return $mails_ids ? $mails_ids : array();
     }
    
    // --------------------------------------------------------------------
    
    /**
     * Get mail data
     *
     * @param $mail_id
     * @return IncomingMail
     */
    public function get_mail($mail_id) {
        $head = imap_rfc822_parse_headers(imap_fetchheader($this->get_imap_stream(), $mail_id, FT_UID));
        
        $CI =& get_instance();
        $CI->load->library('Incoming_Mail');
        $incoming_mail = new $CI->incoming_mail();
        
        $incoming_mail->id = $mail_id;
        $incoming_mail->date = date('Y-m-d H:i:s', isset($head->date) ? strtotime($head->date) : time());
        $incoming_mail->subject = isset($head->subject) ? $this->decode_mime_str($head->subject, $this->imap_server_encoding) : null;
        $incoming_mail->from_name = isset($head->from[0]->personal) ? $this->decode_mime_str($head->from[0]->personal, $this->imap_server_encoding) : null;
        $incoming_mail->from_address = strtolower($head->from[0]->mailbox . '@' . $head->from[0]->host);
        
        if(isset($head->to)) {
            $to_strings = array();
            foreach($head->to as $to) {
                if(!empty($to->mailbox) && !empty($to->host)) {
                    $to_email = strtolower($to->mailbox . '@' . $to->host);
                    $to_name = isset($to->personal) ? $this->decode_mime_str($to->personal, $this->imap_server_encoding) : null;
                    $to_strings[] = $to_name ? "$to_name <$to_email>" : $to_email;
                    $this->incoming_mail->to[$to_email] = $to_name;
                }
            }
            $incoming_mail->to_string = implode(', ', $to_strings);
        }
        
        if(isset($head->cc)) {
            foreach($head->cc as $cc) {
                $incoming_mail->cc[strtolower($cc->mailbox . '@' . $cc->host)] = isset($cc->personal) ? $this->decode_mime_str($cc->personal, $this->imap_server_encoding) : null;
            }
        }
        
        if(isset($head->reply_to)) {
            foreach($head->reply_to as $reply_to) {
                $incoming_mail->reply_to[strtolower($reply_to->mailbox . '@' . $reply_to->host)] = isset($reply_to->personal) ? $this->decode_mime_str($reply_to->personal, $this->imap_server_encoding) : null;
            }
        }
        
        $mail_structure = imap_fetchstructure($this->get_imap_stream(), $mail_id, FT_UID);
         
        if(empty($mail_structure->parts)) {
            $this->_init_mail_part($incoming_mail, $mail_structure, 0);
        }
        else {
            foreach($mail_structure->parts as $part_num => $part_structure) {
                $this->_init_mail_part($incoming_mail, $part_structure, $part_num + 1);
            }
        }
        
        return $incoming_mail;
    }

    // --------------------------------------------------------------------

    protected function _init_mail_part($mail, $part_structure, $part_num) {
            
        $data = $part_num ? imap_fetchbody($this->get_imap_stream(), $mail->id, $part_num, FT_UID) : imap_body($this->get_imap_stream(), $mail->id, FT_UID);
        
        if($part_structure->encoding == 1) {
            $data = imap_utf8($data);
        }
        elseif($part_structure->encoding == 2) {
            $data = imap_binary($data);
        }
        elseif($part_structure->encoding == 3) {
            $data = imap_base64($data);
        }
        elseif($part_structure->encoding == 4) {
            $data = imap_qprint($data);
        }
        $params = array();
        
        if(!empty($part_structure->parameters)) {
            foreach($part_structure->parameters as $param) {
                $params[strtolower($param->attribute)] = $param->value;
            }
        }
        
        if(!empty($part_structure->dparameters)) {
            foreach($part_structure->dparameters as $param) {
                $paramName = strtolower(preg_match('~^(.*?)\*~', $param->attribute, $matches) ? $matches[1] : $param->attribute);
                if(isset($params[$paramName])) {
                    $params[$paramName] .= $param->value;
                }
                else {
                    $params[$paramName] = $param->value;
                }
            }
        }
        
        if(!empty($params['charset'])) {
            // php-imap fix #46 
            //$data = iconv(strtoupper($params['charset']), $this->imap_server_encoding . '//IGNORE', $data);
            $data = mb_convert_encoding($data, $this->imap_server_encoding, $params['charset']);
        }
        
        // attachments
        $attachment_id = $part_structure->ifid
            ? trim($part_structure->id, " <>")
            : (isset($params['filename']) || isset($params['name']) ? mt_rand() . mt_rand() : null);
            
        if($attachment_id) {
            if(empty($params['filename']) && empty($params['name'])) {
                $file_name = $attachment_id . '.' . strtolower($part_structure->subtype);
            } else {
                $file_name = !empty($params['filename']) ? $params['filename'] : $params['name'];
                $file_name = $this->decode_mime_str($file_name, $this->imap_server_encoding);
                $file_name = $this->decode_rfc2231($file_name, $this->imap_server_encoding);
            }
            
            $CI =& get_instance();
            $CI->load->library('Incoming_Mail_Attachment');
            $mailattachment = new $CI->incoming_mail_attachment();
            $mailattachment->id = $attachment_id;
            $mailattachment->name = $file_name;
            if($this->imap_attachemnt_dir) {
                $replace = array(
                    '/\s/' => '_',
                    '/[^0-9a-zA-Z_\.]/' => '',
                    '/_+/' => '_',
                    '/(^_)|(_$)/' => '',
                );
                $file_sys_name = preg_replace('~[\\\\/]~', '', $mail->id . '_' . $attachment_id . '_' . preg_replace(array_keys($replace), $replace, $file_name));
                $mailattachment->uploadfileName = $file_sys_name;
                $mailattachment->filePath = $this->imap_attachemnt_dir . DIRECTORY_SEPARATOR . $file_sys_name;

                file_put_contents($mailattachment->filePath, $data);
            }
            $mail->add_attachment($mailattachment);
            unset($mailattachment); 
        } elseif($part_structure->type == 0 && $data) {
            if(strtolower($part_structure->subtype) == 'plain') {
                $mail->text_plain .= $data;
            } else {
                $mail->text_html .= $data;
            }
        } elseif($part_structure->type == 2 && $data) {
            $mail->text_plain .= trim($data);
        }
        
        if(!empty($part_structure->parts)) {
            foreach($part_structure->parts as $sub_part_num => $sub_part_structure) {
                if($part_structure->type == 2 && $part_structure->subtype == 'RFC822') {
                    $this->_init_mail_part($mail, $sub_part_structure, $part_num);
                } else {
                    $this->_init_mail_part($mail, $sub_part_structure, $part_num . '.' . ($sub_part_num + 1));
                }
            }
        }
        
    }


    
    // --------------------------------------------------------------------
    
    /**
     * Decode Mime String
     *
     * @param $string, $charset
     * @return String
     */
    protected function decode_mime_str($string, $charset = 'utf-8') {
        $new_string = '';
        $elements = imap_mime_header_decode($string);
        for($i = 0; $i < count($elements); $i++) {
            if($elements[$i]->charset == 'default') {
                $elements[$i]->charset = 'iso-8859-1';
            }
            // php-imap fix #46 
            // $new_string .= iconv(strtoupper($elements[$i]->charset), $charset . '//IGNORE', $elements[$i]->text);
            $new_string .= mb_convert_encoding($elements[$i]->text, $charset, $elements[$i]->charset);
        }
        return $new_string;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Decode RFC String
     *
     * @param $string, $charset
     * @return String
     */
    protected function decode_rfc2231($string, $charset = 'utf-8') {
        if(preg_match("/^(.*?)'.*?'(.*?)$/", $string, $matches)) {
            $encoding = $matches[1];
            $data = $matches[2];
            if($this->isUrlEncoded($data)) {
                // php-imap fix #46 
                // $string = iconv(strtoupper($encoding), $charset . '//IGNORE', urldecode($data));
                $string = mb_convert_encoding(urldecode($data), $charset, $encoding);
            }
        }
        return $string;
    }
    
    
    
}


/* End of file MY_Email.php */
/* Location: ./system/application/libraries/MY_Email.php */