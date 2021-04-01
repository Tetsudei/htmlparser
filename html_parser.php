<?php

	// Validate Short-Tags (Selbsschließende HTML Tags);
	$valid_short_tags = array('br', 'img', 'hr');

	$text = 'tr<p><p><span>fugz</span> vbhjo</p>est<div id="blahblah" class="xy">TECHNISCHE DATEN<br>USB Spezifikation 1.1<br></div>RS232 serieller Anschluss mit DB9 Stecker.<br>TECHNISCHE DATEN<br>USB Spezifikation 1.1<br>RS232 serieller Anschluss mit DB9 Stecker.';
	// Maximale Text Länge
	$max_text_length = 100;

	$final_text = get_clean_text($text, $max_text_length);

	echo '<strong>Finaler Text mit ' . $max_text_length . ' Zeichen (mit HTML)</strong>: ' . ($final_text) . '<br />';
	exit;


	/**	 
	 * Iteriert den übergebenen Text und gibt $max_text_length Zeichen reinen Text MIT
	 * HTML Formatierung zurüeck.
	 * Alle HTML Tags, die nach $max_text_length nicht geschlossen wurden,
	 * werden vor der Rückgabe des Textes geschlossen, so dass ein valides HTML entsteht.
	 *
	 * @param	string	$text				Der zu iterierende Text
	 * @param	integer	$max_text_length	Die Anzahl an Zeichen Text ohne Formatierung
	 * 
	 * @return	string						Der iterierte Text auf $max_text_length Zeichen gekürzt.
	 *
	 * @date	03.12.2011
	 * @author	Benedict Klein <benedict.klein91@web.de>
	 */
	function get_clean_text($text, $max_text_length)
	{
		// Flaches Array, das alle nicht-geschlossenen Tags enthält
		$open_tags = array();
		// Ist ein Tag geöffnet und noch nicht geschlossen?
		$tag_open = 0;
		// Parser ist in einem Tag? (Tag "aktiv")
		$in_tag = false;
		// Name des aktiven Tags
		$current_tag = '';
		// Anzahl Zeichen die kein HTML Quelltext sind
		$length_text = 0;
		// Ist der aktuelle Tag ein "Short-Tag"?
		$short_tag = false;

		for ($i = 0; $i < strlen($text); $i++) {
			$c = $text[$i];
			$c_next = $text[$i + 1];

			// Tag Anfang
			if ($c == '<') {
				// Wurde schon ein Tag Anfang gefunden?
				if ($in_tag == false && $c_next != '/') {
					// Neuer Tag
					$tagname = get_tagname($text, $i + 1, $short_tag);
					// Tag Name gefunden?
					if ($tagname != null) {
						//echo '# in tag: ' . $tagname. '<br />';
						// Wir sind in einem Tag
						$in_tag = true;
						$current_tag = $tagname;

						// Zeichen überspringen
						$i += strlen($tagname) - 1;

						continue;
					}

					// Tag nicht gefunden, also kein HTML Tag sondern normaler Text
					// Wird weiter unten am Ende der Schleife gezählt
				}

				// Wird der aktuelle Tag geschlossen? "</..."
				if ($tag_open > 0 && $c_next == '/') {
					// Name des Tags auslesen ($i + 2 um "/" zu überspringen)
					$tagname = get_tagname($text, $i + 2, $dummy);
					// Valider Tag?
					if ($tagname !== null) {
						// Letzten Tag aus dem Array auslesen (ref: end())
						$last_tag = end($open_tags);

						// Aktuellen Schließ-Tag mit letztem Vergleichen
						if ($last_tag == $tagname) {
							//echo '#kick tag ' . $tagname . ' [#' . count($open_tags) . ', el in array: ' . $open_tags[count($open_tags) - 1] . ']<br />';
							// Übereinstimmung, Tag wird geschlossen
							$tag_open--;
							// Letztes Element aus dem Array kicken -> wir brauchen nur nicht-geschlossene Tags
							array_pop($open_tags);
						}

						// Wir haben 2 Zeichen übersprungen </ und Tagname 
						// Also $i aufrechnen
						$i += 2 + strlen($tagname);

						continue;
					}

					// Kein valider Tag.. also reiner Text
					// Wird weiter unten gezählt
				}
			}

			// Tag Anfang wurde schon gefunden, wir suchen das Ende
			if ($in_tag == true) {
				// Ende gefunden
				if ($c == '>') {
					// Tag nur hinzufügen und zählen, wenn es kein Short-Tag ist
					if ($short_tag == false) {
						//echo '#add tag ' . $current_tag . '<br />';
						$open_tags[] = $current_tag;
						
						// Tag wurde verarbeitet (bis zum Ende)
						// Nun muss er geschlossen werden
						$tag_open++;
					} else {
						//echo '#ign tag ' . $current_tag . '<br />';
					}

					// Werte zurücksetzten
					$in_tag = false;
					$current_tag = '';
				} else {
					// Keine Ende in Sicht, Attribut-Text kann verworfen werden
				}

				continue;
			}

			// Weder öffnender noch schliessender Tag, also reiner Text!
			$length_text++;

			// Maximale Text Länge erreicht?
			if ($length_text >= $max_text_length) {
				break;
			}
		}

		// Teil Zeichenkette mit dem n-Zeichen Text auslesen
		$final_text = substr($text, 0, $i + 1);
		// Nicht-geschlossene Tags schließen
		// Array wird dafür umgedreht
		$open_tags = array_reverse($open_tags);
		// Nun alle Tags iterieren und anhängen
		for ($i = 0; $i < count($open_tags); $i++) {
			$final_text .= '</' . $open_tags[$i] . '>';
		}


		return $final_text;
	}


	function get_tagname($text, $i_start, &$short_tag) 
	{
		global $valid_short_tags;
		$short_tag = false;

		//echo 'get_tagname: ' . $i_start . ' (' . htmlentities(substr($text, $i_start, 3)) . ')<br />';
		// Alles auslesen bis Leerzeichen oder /
		for ($i = $i_start; $i < strlen($text); $i++) {
			// <tag>
			// <tag/ 
			// <tag 
			if ($text[$i] == '>' || $text[$i] == '/' || $text[$i] == ' ') {
				// Trenner gefunden
				// Länge des Tag Namens
				$tagname_length = ($i - $i_start);
				// Tag Name auslesen
				$tagname = substr($text, $i_start, $tagname_length);
				//echo ' - tagname: ' . htmlentities($tagname) . '<br />';

				// Short-Tag?
				$short_tag = in_array($tagname, $valid_short_tags);

				// TODO: Tag hier auf valide/mögliche Tags prüfen
				//		 Könnte ja auch ein simples "<moep>" sein
				return $tagname;
			} else {
				//echo ' - char ' . $i . ': ' . $text[$i] . '<br />';
			}
		}

		// Trenner nicht gefunden
		return null;
	}


?>
