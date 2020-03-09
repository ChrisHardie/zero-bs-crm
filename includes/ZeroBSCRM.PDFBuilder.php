<?php
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V3.0.8
 *
 * Copyright 2020, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 15/02/2020
 */

#} Require DOMPDF
if (file_exists(ZEROBSCRM_PATH . 'includes/lib/dompdf-0-8-2/autoload.inc.php')) require_once(ZEROBSCRM_PATH . 'includes/lib/dompdf-0-8-2/autoload.inc.php');

#} Required
use FontLib\Font;


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


/* ======================================================
	Font Loading Funcs
   ====================================================== */



	// installs fonts (which have already been downloaded, but are not marked installed)
	// can use zeroBSCRM_PDFBuilder_retrieveFonts() if from scratch (downloads + installs)
	function zeroBSCRM_PDFBuilder_installFonts(){

		// also install the font(s) if not already installed (if present)
		$fontsInstalled = zeroBSCRM_getSetting('pdf_fonts_installed');
		if ($fontsInstalled !== 1 && file_exists(ZEROBSCRM_PATH . 'includes/lib/dompdf-fonts/fonts-info.txt')){

			#} attempt to install
			$fontDir = ZEROBSCRM_PATH . 'includes/lib/dompdf-fonts/';
			if (zeroBSCRM_PDFBuilder_loadFont(
			    'NotoSansGlobal', 
			    $fontDir.'NotoSans-Regular.ttf', 
			    $fontDir.'NotoSans-Bold.ttf', 
			    $fontDir.'NotoSans-Italic.ttf', 
			    $fontDir.'NotoSans-BoldItalic.ttf'
			  )){

				#} update setting
				global $zbs;
				$zbs->settings->update('pdf_fonts_installed',1);

			}

		}

	}


	#} retrieve (and install) fonts which dompdf uses to provide global lang supp
	function zeroBSCRM_PDFBuilder_retrieveFonts(){

		#} Check if already downloaded libs:
		if (!file_exists(ZEROBSCRM_PATH.'includes/lib/dompdf-fonts/fonts-info.txt')){

			global $zbs;

			#} Libs appear to need downloading..
				
				set_time_limit(0); #} Just incase
				
				#} dirs
				$workingDir = ZEROBSCRM_PATH.'temp'.time(); if (!file_exists($workingDir)) wp_mkdir_p($workingDir);
				$endingDir = ZEROBSCRM_PATH.'includes/lib/dompdf-fonts'; if (!file_exists($endingDir)) wp_mkdir_p($endingDir);

				if (file_exists($endingDir) && file_exists($workingDir)){

					#} Retrieve zip
					$libs = zeroBSCRM_retrieveFile($zbs->urls['extdlrepo'].'pdffonts.zip',$workingDir.'/pdffonts.zip');

					#} Expand
					if (file_exists($workingDir.'/pdffonts.zip')){

						#} Should checksum?

						#} For now, expand zip
						$expanded = zeroBSCRM_expandArchive($workingDir.'/pdffonts.zip',$endingDir.'/');

						#} Check success?
						if (file_exists($endingDir.'fonts-info.txt')){

							#} All appears good, clean up
							if (file_exists($workingDir.'/pdffonts.zip')) unlink($workingDir.'/pdffonts.zip');
							if (file_exists($workingDir)) rmdir($workingDir);

							// install em
							zeroBSCRM_PDFBuilder_installFonts();

						} else {

							#} Add error msg
							global $zbsExtensionInstallError;
							$zbsExtensionInstallError = __('ZBS CRM was not able to extract the libraries it needs to in order to install PDF Engine.',"zero-bs-crm").' '.__('(fonts)','zero-bs-crm');

						}


					} else {

						#} Add error msg
						global $zbsExtensionInstallError;
						$zbsExtensionInstallError = __('ZBS CRM was not able to download the libraries it needs to in order to install PDF Engine.',"zero-bs-crm").' '.__('(fonts)','zero-bs-crm');

					}


				} else {

					#} Add error msg
					global $zbsExtensionInstallError;
					$zbsExtensionInstallError = __('ZBS CRM was not able to create the directories it needs to in order to install PDF Engine.',"zero-bs-crm").' '.__('(fonts)','zero-bs-crm');

				}


		} else {

			#} Already exists...

				// check they're installed
				zeroBSCRM_PDFBuilder_installFonts();

		}

		#} Return fail
		return false;

	}


   // Loads a font file collection (.ttf's) onto the server for dompdf
   // only needs to fire once
   function zeroBSCRM_PDFBuilder_loadFont($fontName='', $normalFile='', $boldFile='', $italicFile='', $boldItalicFile=''){

    global $zbs;

    if ($zbs->isDAL3() && !empty($fontName)
    	 && file_exists($normalFile)
    	 && file_exists($boldFile)
    	 && file_exists($italicFile)
    	 && file_exists($boldItalicFile)
		){

        // brutal.
        if (!zeroBSCRM_isZBSAdminOrAdmin()) return false;

        #} PDF Install check (importantly skipp the fontcheck with false first param)
        zeroBSCRM_extension_checkinstall_pdfinv(false);

        #} Initialise dompdf
        $dompdf = new Dompdf\Dompdf();

  		#} Install the font(s)
		return zeroBSCRM_PDFBuilder_install_font_family($dompdf, $fontName, $normalFile, $boldFile, $italicFile, $boldItalicFile);

	}

	return false; 

}



	/**
	 * Installs a new font family
	 * This function maps a font-family name to a font.  It tries to locate the
	 * bold, italic, and bold italic versions of the font as well.  Once the
	 * files are located, ttf versions of the font are copied to the fonts
	 * directory.  Changes to the font lookup table are saved to the cache.
	 *
	 * This is an an adapted version of install_font_family() from https://github.com/dompdf/utils
	 *
	 * @param Dompdf $dompdf      dompdf main object 
	 * @param string $fontname    the font-family name
	 * @param string $normal      the filename of the normal face font subtype
	 * @param string $bold        the filename of the bold face font subtype
	 * @param string $italic      the filename of the italic face font subtype
	 * @param string $bold_italic the filename of the bold italic face font subtype
	 *
	 * @throws Exception
	 */
	function zeroBSCRM_PDFBuilder_install_font_family($dompdf, $fontname, $normal, $bold = null, $italic = null, $bold_italic = null, $debug = false) {
	  
	  try {

		  $fontMetrics = $dompdf->getFontMetrics();
		  
		  // Check if the base filename is readable
		  if ( !is_readable($normal) )
		    throw new Exception("Unable to read '$normal'.");

		  $dir = dirname($normal);
		  $basename = basename($normal);
		  $last_dot = strrpos($basename, '.');
		  if ($last_dot !== false) {
		    $file = substr($basename, 0, $last_dot);
		    $ext = strtolower(substr($basename, $last_dot));
		  } else {
		    $file = $basename;
		    $ext = '';
		  }

		  if ( !in_array($ext, array(".ttf", ".otf")) ) {
		    throw new Exception("Unable to process fonts of type '$ext'.");
		  }

		  // Try $file_Bold.$ext etc.
		  $path = "$dir/$file";
		  
		  $patterns = array(
		    "bold"        => array("_Bold", "b", "B", "bd", "BD"),
		    "italic"      => array("_Italic", "i", "I"),
		    "bold_italic" => array("_Bold_Italic", "bi", "BI", "ib", "IB"),
		  );
		  
		  foreach ($patterns as $type => $_patterns) {
		    if ( !isset($$type) || !is_readable($$type) ) {
		      foreach($_patterns as $_pattern) {
		        if ( is_readable("$path$_pattern$ext") ) {
		          $$type = "$path$_pattern$ext";
		          break;
		        }
		      }
		      
		      if ( is_null($$type) )
		        if ($debug) echo ("Unable to find $type face file.\n");
		    }
		  }

		  $fonts = compact("normal", "bold", "italic", "bold_italic");
		  $entry = array();

		  // Copy the files to the font directory.
		  foreach ($fonts as $var => $src) {
		    if ( is_null($src) ) {
		      $entry[$var] = $dompdf->getOptions()->get('fontDir') . '/' . mb_substr(basename($normal), 0, -4);
		      continue;
		    }

		    // Verify that the fonts exist and are readable
		    if ( !is_readable($src) )
		      throw new Exception("Requested font '$src' is not readable");

		    $dest = $dompdf->getOptions()->get('fontDir') . '/' . basename($src);

		    if ( !is_writeable(dirname($dest)) )
		      throw new Exception("Unable to write to destination '$dest'.");

		    if ($debug) echo "Copying $src to $dest...\n";

		    if ( !copy($src, $dest) )
		      throw new Exception("Unable to copy '$src' to '$dest'");
		    
		    $entry_name = mb_substr($dest, 0, -4);
		    
		    if ($debug) echo "Generating Adobe Font Metrics for $entry_name...\n";
		    
		    $font_obj = Font::load($dest);
		    $font_obj->saveAdobeFontMetrics("$entry_name.ufm");
		    $font_obj->close();

		    $entry[$var] = $entry_name;

		  }

		  // Store the fonts in the lookup table
		  $fontMetrics->setFontFamily($fontname, $entry);

		  // Save the changes
		  $fontMetrics->saveFontFamilies();

		  // Fini
		  return true;

		} catch (Exception $e){

			// nada

		}

		return false;

	}


/* ======================================================
	/ Font Loading Funcs
   ====================================================== */