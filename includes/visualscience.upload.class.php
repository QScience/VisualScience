<?php
/**
 * @file
 * Provides everything to handle upload and downloading files.
 */

class VisualscienceUpload {

  /**
   * Creates the form to upload files to the server.
   * 
   * Page callback: Upload form
   * 
   * @return array             
   *   Array used to generate the form
   */
  public function visualscienceUploadForm($form, &$form_state) {
    $form['visualscience_upload_file'] = array(
      '#type' => 'file',
      '#size' => 20,
      '#required' => FALSE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    $form['#submit'][] = 'visualscience_upload_submit';
    return $form;
  }

  /**
   * Allows the download of a file to an authenticated and legitime user.
   */
  public function visualscienceGetFileWithId() {
    $file_id = floatval($_GET['id']);
    global $user;
    global $base_url;
    $uid = $user->uid;
    if ($uid) {
      $result = db_query('SELECT * FROM {visualscience_uploaded_files} WHERE uid = :uid AND fid = :fid', array(':uid' => $uid, ':fid' => $file_id));
      $result = $result->fetchObject();
      if (!is_null($result) && !empty($result)) {
        $url = $result->url;
        $name = $result->name;
        $mimetype = file_get_mimetype($name);
        $headers = array(
          'Content-Type' => $mimetype,
          'Content-Disposition' => ' attachment; filename=' . $name,
          'Content-Transfer-Encoding' => 'binary',
          'Expires' => '0',
          'Cache-Control' => 'must-revalidate',
          'Pragma' => 'no-cache',
          'Content-Length' => filesize($url),
        );
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
          $headers['Cache-Control'] = 'must-revalidate, post-check=0, pre-check=0';
          $headers['Pragma'] = 'public';
        }
        file_transfer($url, $headers);
      }
      else {
        drupal_set_message(t("You don't have the permission to access the file."), 'error');
        header('Location: ' . $base_url . '/visualscience/');
      }
    }
    else {
      drupal_set_message(t('Please log in to access uploaded files.'), 'warning');
      header('Location: ' . $base_url . '/user?destination=' . substr(request_uri(), strpos(request_uri(), '/visualscience/file') + 1));
    }
  }

  /**
   * Submits upload callback, register (db and server) file if needed.
   * 
   * TODO: Handle multiple uploads(Hint:Look for MultiUpload File Widget).
   */
  public function visualscienceUploadSubmit($form, &$form_state) {
    $dir = 'private://';
    $validators = array();
    $file = 'visualscience_upload_file';
    if (!empty($file)) {
      $file = file_save_upload($file, $validators, $dir, FILE_EXISTS_RENAME);
      if (!empty($file)) {
        global $user;
        global $base_url;
        $uid = $user->uid;
        $email = $user->mail;
        $query = db_insert('visualscience_uploaded_files')->fields(array(
          'uid' => $uid,
          'email' => $email,
          'name' => $file->filename,
          'url' => $file->uri,
          ));
        $query->execute();
        $result = db_query('SELECT * FROM {visualscience_uploaded_files} WHERE uid = :uid AND url = :url', array(':uid' => $uid, ':url' => $file->uri))->fetchObject();
        $id = $result->fid;
        $vs_url = drupal_get_path('module', 'visualscience');
        if (strpos($vs_url, '?')) {
          // Handling clean url.
          drupal_set_message(t('The file has been uploaded to: @url', array('@url' => $base_url . '/visualscience/file&id=' . $id)));
        }
        else {
          drupal_set_message(t('The file has been uploaded to: @url', array('@url' => $base_url . '/visualscience/file?id=' . $id)));
        }
      }
      else {
        form_set_error(t('An error occured when uploading the file.'));
      }
    }
    else {
      form_set_error(t('You did not chose a file.'));
    }
  }
}
