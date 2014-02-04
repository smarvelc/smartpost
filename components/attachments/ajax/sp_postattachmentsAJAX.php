<?php
/*
 * AJAX-Related functions for all
 * sp_postAttachments components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postAttachmentsAJAX")) {
	class sp_postAttachmentsAJAX{
		
		static function init(){
			add_action('wp_ajax_saveAttachmentsDescAJAX', array('sp_postAttachmentsAJAX', 'saveAttachmentsDescAJAX'));		
			add_action('wp_ajax_attachmentsUploadAJAX', array('sp_postAttachmentsAJAX', 'attachmentsUploadAJAX'));
			add_action('wp_ajax_attachmentsDeleteAttachmentAJAX', array('sp_postAttachmentsAJAX', 'attachmentsDeleteAttachmentAJAX'));			
		}
		
		function saveAttachmentsDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postAttachments')){
                    header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                    echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $attachmentID = (int)  $_POST['attachmentID'];
            $desc = (string) stripslashes_deep($_POST['desc']);
            $attachmentsComponent = new sp_postAttachments($compID);

            if( is_wp_error($attachmentsComponent->errors) ){
                header( "HTTP/1.0 409 " . $attachmentsComponent->errors->get_error_message() );
            }else{
                $success = $attachmentsComponent->setAttachmentDescription($desc, $attachmentID);
                if($success === false){
                    header("HTTP/1.0 409 Could not save link description.");
                }else{
                    echo json_encode(array('success' => true));
                }

            }
            exit;
		}		
		
		static function attachmentsDeleteAttachmentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }
				
            if(!class_exists('sp_postAttachments')){
                header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if( empty($_POST['attachmentID']) ){
                header("HTTP/1.0 409 Could find attachment ID to udpate.");
                exit;
            }
				
            $id = (int) $_POST['attachmentID'];
            $compID = (int) $_POST['compID'];
            $attachmentsComponent = new sp_postAttachments($compID);
            $postThumbID   = get_post_thumbnail_id($attachmentsComponent->getPostID());
            $attachmentIDs = $attachmentsComponent->getAttachments();
				
            //Delete the attachment
            wp_delete_attachment( $id, true );
            $idKey = array_search($id, $attachmentIDs);

            if( !empty($idKey) )
                    unset( $attachmentIDs[$idKey] );

            $success = $attachmentsComponent->setAttachmentIDs( $attachmentIDs );
            if( $success === false ){
                header("HTTP/1.0 409 Could not successfully set attachment ID.");
                exit;
            }

            echo json_encode( array('sucess' => true) );
            exit;
		}

        /**
         * Handles attachment uploads
         */
        static function attachmentsUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postAttachments')){
                header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if( empty($_FILES) ){
                header("HTTP/1.0 409 Files uploaded are empty!");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $attachmentsComponent = new sp_postAttachments($compID);

            if( is_wp_error( $attachmentsComponent->errors ) ){
                header( "HTTP/1.0 409 Error: " . $attachmentsComponent->errors->get_error_message() );
                exit;
            }
            // Upload the file
            $file = sp_core::chunked_plupload('sp-attachments-upload');

            if( file_exists($file) ){

                $allowedExts = $attachmentsComponent->allowedExts;
                if( !empty($allowedExts) ){
                    $allowed = sp_core::validateExtension($_FILES['sp-attachments-upload']['name'], $allowedExts);
                }else{
                    $allowed = true;
                }

                if($allowed){
                    $desc = $_FILES['sp-attachments-upload']['name'];
                    $attach_id = sp_core::create_attachment($file, $attachmentsComponent->getPostID(), $desc );

                    array_push( $attachmentsComponent->attachmentIDs, $attach_id );
                    $success = $attachmentsComponent->update();

                    if( $success === false ){
                        header("HTTP/1.0 409 Could not successfully set attachment ID.");
                        exit;
                    }
                    echo $attachmentsComponent->renderAttachmentRow( $attach_id );
                }else{
                    header("HTTP/1.0 409 File type not allowed.");
                    exit;
                }
            }else if( $file !== false && !file_exists( $file ) ){
                header( "HTTP/1.0 409 Could not successfully upload file!" );
                exit;
            }
            exit;
		}
	}
}