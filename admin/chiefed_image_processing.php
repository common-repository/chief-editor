<?php
if (! defined ( 'ABSPATH' )) {
	exit ();
}
$path = sprintf ( "%s/meta_boxes.php", dirname ( __FILE__ ) );
require_once ($path);
$path = sprintf ( "%s/chiefed_utils.php", dirname ( dirname ( __FILE__ ) ) );
require_once ($path);
class Chiefed_Image_Processor {
	function __construct() {
	}
	static function createImagesSubFolder($directory) {
		$imagesDir = $directory . '/' . 'images';
		if (! is_dir ( $imagesDir )) {
			// Create our directory.
			mkdir ( $imagesDir, 0755, true );
		}
		$tempImagesDir = $directory . '/' . 'tempImages';
		
		if (is_dir ( $tempImagesDir )) {
			$it = new RecursiveDirectoryIterator ( $tempImagesDir, RecursiveDirectoryIterator::SKIP_DOTS );
			$files = new RecursiveIteratorIterator ( $it, RecursiveIteratorIterator::CHILD_FIRST );
			foreach ( $files as $file ) {
				if ($file->isDir ()) {
					rmdir ( $file->getRealPath () );
				} else {
					unlink ( $file->getRealPath () );
				}
			}
			rmdir ( $tempImagesDir );
		}
		if (! is_dir ( $tempImagesDir )) {
			// Create our directory.
			mkdir ( $tempImagesDir, 0755, true );
		}
		
		return array (
				'temp' => trailingslashit ( $tempImagesDir ),
				'images' => trailingslashit ( $imagesDir ),
				'root' => $directory,
		);
	}
	static function extractImages($imagesSourceFile, $tempImageFolder, $destinationDirectory) {
		$msg = __ ( 'extracting all images from file provided and write them in appropriate work folder from ' . $imagesSourceFile );
		CHIEFED_UTILS::getLogger ()->debug ( $msg );
		$filemode = 0755;
		chmod ( $tempImageFolder, $filemode );
		chmod ( $destinationDirectory, $filemode );
		// $destinationDirectory = $destinationDirectory;
		$msg = __ ( 'destination folder is : ' . $tempImageFolder );
		CHIEFED_UTILS::getLogger ()->debug ( $msg );
		$imagesOutput = array ();
		if (stripos ( $imagesSourceFile, '.docx' ) !== false) {
			CHIEFED_UTILS::getLogger ()->debug ( "#### Extracting images from DOCX : " . $imagesSourceFile . " ####" );
			// $cmd = "docx2txt -i ".$destinationDirectory." ".$imagesSourceFile;
			$pyCmd = "python -c 'import docxpy; file =\"" . $imagesSourceFile . "\";text = docxpy.process(file, \"" . $tempImageFolder . "\")'";
			
			// CHIEFED_UTILS::getLogger()->debug($cmd);
			CHIEFED_UTILS::getLogger ()->debug ( $pyCmd );
			// $retValue = exec($cmd, $imagesOutput);
			$retValue = shell_exec ( $pyCmd );
		} else if (stripos ( $imagesSourceFile, '.doc' ) !== false) {
			CHIEFED_UTILS::getLogger ()->debug ( "#### Extracting images from DOC : " . $imagesSourceFile . " ####" );
			$pyCmd = "python -c 'import docxpy; file =\"" . $imagesSourceFile . "\";text = docxpy.process(file, \"" . $tempImageFolder . "\")'";
			CHIEFED_UTILS::getLogger ()->debug ( $pyCmd );
			$retValue = shell_exec ( $pyCmd );
		} else if (stripos ( $imagesSourceFile, '.pdf' ) !== false) {
			CHIEFED_UTILS::getLogger ()->debug ( "#### Extracting images from PDF : " . $imagesSourceFile . " ####" );
			$cmd = "pdfimages -png " . $imagesSourceFile . " " . $tempImageFolder;
			CHIEFED_UTILS::getLogger ()->debug ( "Using command: " . $cmd );
			$retValue = shell_exec ( $cmd );
		} else if (stripos ( $imagesSourceFile, '.pptx' ) !== false) {
			CHIEFED_UTILS::getLogger ()->debug ( "#### Extracting images from PPTX : " . $imagesSourceFile . " ####" );
			// plugin_dir_path( __FILE__ ) . 'includes/admin-functions.php'
			$scriptFile = plugin_dir_path ( __DIR__ ) . 'py/extractImagesFromPPTX.py';
			CHIEFED_UTILS::getLogger ()->debug ( $scriptFile );
			// $scriptContent = 'from pptx import Presentation;import os;prs =
			// Presentation("'.$imagesSourceFile.'");for slide in prs.slides:;for shape in
			// slide.shapes:;if hasattr(shape, "text"):print(shape.text)';
			
			// $pyCmd = "python -c '".$scriptContent."'";
			$pyCmd = $scriptFile . ' ' . $imagesSourceFile . ' ' . $tempImageFolder;
			CHIEFED_UTILS::getLogger ()->debug ( $pyCmd );
			// $output = shell_exec
			$retValue = shell_exec ( $pyCmd );
		} else if (stripos ( $imagesSourceFile, '.zip' ) !== false) {
			CHIEFED_UTILS::getLogger ()->debug ( "#### Extracting images from ZIP : " . $imagesSourceFile . " ####" );
			$zip = new ZipArchive ();
			if ($zip->open ( $imagesSourceFile ) === TRUE) {
				$zip->extractTo ( $tempImageFolder );
				$zip->close ();
				$retValue = 'zip extract : ok';
			} else {
				$retValue = '#### ERROR zip extract : ko';
			}
		} else {
			CHIEFED_UTILS::getLogger ()->debug ( "#### ERROR Unknown file type : " . $imagesSourceFile . " ####" );
		}
		CHIEFED_UTILS::getLogger ()->debug ( $retValue );
		$imagesArray = array ();
		CHIEFED_UTILS::getLogger ()->debug ( "#### Wait for image extraction script ####" );
		// sleep ( 10 );
		CHIEFED_UTILS::getLogger ()->debug ( "#### Change permissions ####" );
		$iterator = new RecursiveIteratorIterator ( new RecursiveDirectoryIterator ( $tempImageFolder ) );
		foreach ( $iterator as $item ) {
			// CHIEFED_UTILS::getLogger()->debug($item);
			chmod ( $item, $filemode );
		}
		
		CHIEFED_UTILS::getLogger ()->debug ( "#### Rename files ####" );
		// rename all images with numbers (hoping legends will match...)
		$directory = $tempImageFolder;
		$idx = 1;
		$imgRenamed = 0;
		$dirname = trailingslashit ( $destinationDirectory );
		foreach ( glob ( $directory . "*" ) as $filename ) {
			// $dirname = trailingslashit($destinationDirectory);
			$img_file = realpath ( $filename );
			CHIEFED_UTILS::getLogger ()->debug ( $img_file );
			$captionPattern = '/^__\d+__/';
			if (preg_match ( $captionPattern, basename ( $img_file ), $matches ) === 1) {
				CHIEFED_UTILS::getLogger ()->warn ( "Pattern already set : " . basename ( $img_file ) );
				
				// $newFilename = $dirname . basename ( $img_file );
			} else {
				$prefix = "__" . $idx . "__";
				$newBasename = $prefix . basename ( $img_file );
				
				$newFilename = $dirname . $newBasename;
				rename ( $img_file, $newFilename );
				$imgRenamed ++;
			}
			CHIEFED_UTILS::getLogger ()->debug ( $newFilename );
			
			$idx ++;
		}
		
		CHIEFED_UTILS::getLogger ()->debug ( "Img renamed: " . $imgRenamed );
		CHIEFED_UTILS::getLogger ()->debug ( $retValue );
		CHIEFED_UTILS::getLogger ()->debug ( $imagesOutput );
		
		return $destinationDirectory;
	}
	static function addImagesToXML($imagesTag, $imgPath) {
		$iterator = new RecursiveIteratorIterator ( new RecursiveDirectoryIterator ( $imgPath ) );
		foreach ( $iterator as $item ) {
			if ($item->getFilename () == '..' || substr ( $item->getFilename (), 0, 1 ) == '.') {
				CHIEFED_UTILS::getLogger ()->trace ( "--- SKIP image file " . $item->getFilename () );
				continue;
			} else {
				CHIEFED_UTILS::getLogger ()->debug ( "---> Process image file " . $item->getFilename () );
			}
			
			$imageContainerTag = $imagesTag->addChild ( 'image_container' );
			$imageTag = $imageContainerTag->addChild ( 'image' );
			
			$info = pathinfo ( $item->getPathname () );
			CHIEFED_UTILS::getLogger ()->debug ( $info );
			$image_file_name_without_ext = basename ( $item->getPathname (), '.' . $info ['extension'] );
			CHIEFED_UTILS::getLogger ()->debug ( $image_file_name_without_ext );
			$captionPattern = '/^__\d+__/';
			$matches = array ();
			$caption = $image_file_name_without_ext . " Caption";
			if (preg_match ( $captionPattern, $image_file_name_without_ext, $matches ) === 1) {
				CHIEFED_UTILS::getLogger ()->debug ( "try to get caption for img " . $image_file_name_without_ext );
				$img_number = reset ( $matches );
				$img_number = str_replace ( "_", '', $img_number );
				CHIEFED_UTILS::getLogger ()->debug ( $img_number );
				if (array_key_exists ( $img_number, $allCaptions )) {
					
					$caption = $allCaptions [$img_number];
					CHIEFED_UTILS::getLogger ()->debug ( $img_number . ") Caption found : " . $caption );
				} else {
					CHIEFED_UTILS::getLogger ()->warn ( "No caption match for img " . $img_number );
				}
				
				// $newFilename = $dirname . basename($img_file);
			}
			
			$inddImgPath = Chiefed_Image_Processor::createInDesignImgPath ( $item->getPathname () );
			$imageTag->addAttribute ( 'href', $inddImgPath );
			$imageTag->addAttribute ( 'filename', $item->getFilename () );
			$imageTag->addAttribute ( 'caption', $caption );
			$imageContainerTag->addChild ( 'filename', $item->getFilename () );
			$imageContainerTag->addChild ( 'filepath', $inddImgPath );
			$imageContainerTag->addChild ( 'originalFilepath', $item->getPathname () );
			$imageContainerTag->addChild ( 'caption', $caption );
			$imageContainerTag->addChild ( 'rights', 'Droits Réservés' );
		}
	}
	static function createInDesignImgPath($input) {
		// $result = $input;
		$pathFromIndesign = "file://" . $input;
		// <image href="file:///Volumes/IDMAC/Export/2018/02/12/test-PPTX/images/12_image.JPG"></image>
		// <image href="file:///volume1/IDMAC/Export/2018/02/12/test-PPTX/images/12_image.JPG"></image>
		$search = get_option ( 'chiefed_xml_exports_path_search' );
		$replace = get_option ( 'chiefed_xml_exports_path_replace' );
		
		$pathFromIndesign = str_replace ( $search, $replace, $pathFromIndesign );
		
		return $pathFromIndesign;
	}
	static function attachImagesToPostAndAppendAsGallery($ID, $imagesOutputFolder) {
		$result = '';
		$files = glob ( $imagesOutputFolder . '/*.png' ); // get all file names
		CHIEFED_UTILS::getLogger ()->debug ( "Images extracted in " . $imagesOutputFolder . " : " . count ( $files ) );
		if (!count($files)){
			CHIEFED_UTILS::getLogger ()->warn ("No images extracted");
			return '';
			
		}
		$attachedImagesArray = array();
		foreach ( $files as $file ) { // iterate files
			if (is_file ( $file )) {
				CHIEFED_UTILS::getLogger ()->debug ( "Attaching image : " . $file );
				$attachedImagesArray [] = self::attach_image_to_post ( $file, $ID );
			}
		}
		
		$result .= implode('<br/>',$attachedImagesArray);
		// add image gallery [gallery ids="729,732,731,720"]
		// $postarr['post_content'] = $postarr['post_content'] . '[gallery ids="'.implode(',',$attachedImagesArray).'"]';
		$currentPost = get_post($ID);		
		$currentPostContent = $currentPost->post_content;
		
		// Update post 
		$update_post = array (
				'ID' => $ID,
				'post_content' => $currentPostContent . '[gallery size="full" link="file" columns="4" ids="' . implode ( ',', $attachedImagesArray ) . '"]' 
		);
		
		// Update the post into the database
		$updated_id = wp_update_post ( $update_post );
		if (is_wp_error ( $updated_id )) {
			$errors = $updated_id->get_error_messages ();
			$result .= implode('<br/>',$errors);
			foreach ( $errors as $error ) {
				CHIEFED_UTILS::getLogger ()->error ( $error );
				// echo $error;
			}
		}
		
		return $result;
	}
	static function attach_image_to_post($filename, $post_id) {
		
		// $filename should be the path to a file in the upload directory.
		// $filename = '/path/to/uploads/2013/03/filename.jpg';
		
		// The ID of the post this attachment is for.
		$parent_post_id = $post_id;
		
		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype ( basename ( $filename ), null );
		
		// Get the path to the upload directory.
		$wp_upload_dir = wp_upload_dir ();
		
		// Prepare an array of post data for the attachment.
		$attachment = array (
				'guid' => $wp_upload_dir ['url'] . '/' . basename ( $filename ),
				'post_mime_type' => $filetype ['type'],
				'post_title' => preg_replace ( '/\.[^.]+$/', '', basename ( $filename ) ),
				'post_content' => '',
				'post_status' => 'inherit' 
		);
		
		// Insert the attachment.
		$attach_id = wp_insert_attachment ( $attachment, $filename, $parent_post_id );
		
		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once (ABSPATH . 'wp-admin/includes/image.php');
		
		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata ( $attach_id, $filename );
		wp_update_attachment_metadata ( $attach_id, $attach_data );
		
		set_post_thumbnail ( $parent_post_id, $attach_id );
		
		return $attach_id;
	}
}
new Chiefed_Image_Processor ();