<?php
/**
 * @file
 * The class that manages sending a mail through the Drupal instance.
 */
class VisualscienceMessage
{
  
  /**
   * Allows the sending of a mail through this Drupal instance.
   * 
   * @return int 
   *   Print 1 if everything went fine, else 0.
   */
  public function visualscience_send_message() {
    $subject = check_plain($_POST['subject']);
    $email = check_plain($_POST['recipients']['email']);
    // $name =  check_plain($_POST['recipients']['name']);.
    $message = check_plain($_POST['message']);
    // [0][0] will give the name of object n°0, while [0][1] will give its URL.
    $attachments = $this->sanitizeArray($_POST['attachments']);
    if ($attachments[0]) {
      $attachments_text = t('<br /><h3>Attached Files</h3>');
      foreach ($attachments as $entry) {
        $attachments_text .= t('- <a href="' . $entry[1] . '" _target="blank">' . $entry[0] . '</a><br />');
      }
    } else {
      $attachments_text = '';
    }
    $final_text = $message . check_plain($attachments_text);
    
    
    global $user;
    $module = 'VisualScience';
    $key = uniqid('mail');
    $language = language_default();
    $params = array();
    $from = $user->mail;
    $send = FALSE;
    $message = drupal_mail($module, $key, $email, $language, $params, $from, $send);
    
    $message['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed';
    $message['headers']['From'] = $message['headers']['Sender'] = $message['headers']['Return-Path'] = $from;
    $message['subject'] = $subject;
    $message['body'] = $final_text;
    
    // Retrieve the responsible implementation for this message.
    $system = drupal_mail_system($module, $key);
    
    // Send e-mail.
    $message['result'] = $system->mail($message);
    // If no errors, let's add file access to the user.
    if ($message['result'] == 1) {
      //getting user id from email.
      $users = db_query_range('SELECT uid FROM {users} WHERE mail = :mail', 0, 1, array(
        ':mail' => $email
      ));
      // In the "impossible" case where two emails are the same in the db.
      $users = $users->fetchObject();
      $uid = $users->uid;
      // actually adding the access to the user.
      if (!is_null($uid) && isset($uid)) {
        foreach ($attachments as $file) {
          $query = db_insert('visualscience_uploaded_files')->fields(array(
            'uid' => $uid,
            'email' => $from,
            'name' => $file[0],
            'url' => $file[1],
          ));
          $query->execute();
        }
      }
      echo '1';
    } 
    else {
      echo '0';
    }
  }
  
  /**
   * Sanitzes the content of an array recursively.
   * 
   * @param array $un_safe_array 
   *   The array to sanitize.
   *   
   * @return array
   *   The array sanitized.
   */
  protected function sanitizeArray($un_safe_array) {
    $safe_array = array();
    foreach ($un_safe_array as $entry) {
      if (gettype($entry) == 'array') {
        array_push($safe_array, $this->sanitizeArray($entry));
      } 
      else {
        array_push($safe_array, check_plain($entry));
      }
    }
    return $safe_array;
  }
}
