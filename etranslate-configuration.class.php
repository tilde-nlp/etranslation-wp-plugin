<?php

class eTranslateConfiguration {
	static function getAPIKey() {
		return apply_filters(__METHOD__, trim(get_option('etranslate_api_key')));
	}

	static function getAPIServer() {
		$choice = get_option('etranslate_api_server');
		if( !$choice ) {
			$choice = 'paid_api';
		}
		$possibilities = self::geteTranslateAPIServers();

		if (isset( $possibilities[$choice])) {
			return apply_filters(__METHOD__, $possibilities[$choice]['server'], $choice, $possibilities);
		}
		else {
			return apply_filters(__METHOD__, false, $choice, $possibilities);
		}
	}

	static function geteTranslateAPIServers() {
		$array = array(
			'paid_api'	=> array( 
				'server'	=> 'https://api.deepl.com/v2/', 
				'description'	=> __('Regular paid API Plan (https://api.deepl.com/v2/)', 'etranslate')
			),
			'free_plan'	=> array(
				'server'	=> 'https://api-free.deepl.com/v2/',
				'description'	=> __('New (2021) free API plan (https://api-free.deepl.com)', 'etranslate' )
			),
		);
		return $array;
	}

	static function getLogLevel() {
		return apply_filters(__METHOD__, get_option('etranslate_log_level'));
	}
	
	static function getMetaBoxPostTypes() {
		return apply_filters(__METHOD__, get_option('etranslate_metabox_post_types'));
	}
	static function getMetaBoxDefaultBehaviour() {
		return apply_filters(__METHOD__, get_option('etranslate_metabox_behaviour'));
	}
	static function getDefaultTargetLanguage() {
		return apply_filters(__METHOD__, get_option('etranslate_default_language'));
	}

	static function getDisplayedLanguages() {
		$value = get_option('etranslate_displayed_languages') ;
		return apply_filters(__METHOD__, $value);
	}
	static function getMetaBoxContext() {
		return apply_filters(__METHOD__, get_option('etranslate_metabox_context'));
	}
	static function getMetaBoxPriority() {
		return apply_filters(__METHOD__, get_option('etranslate_metabox_priority'));
	}

	static function usingMultilingualPlugins() {
		return false;
	}

    /**
     * 0 = activated
     * 1 = activated and setup
     */
	static function isPluginInstalled() {
		return get_option('etranslate_plugin_installed');
	}

	static function execWorks() {
		if (exec('echo EXEC') == 'EXEC') {
		 return true;
		}
		return false;
	}

	static function validateLang($language_string, $output = 'assource') {
		$language_string = filter_var($language_string, FILTER_SANITIZE_STRING);
		$language_string = str_replace('-', '_', $language_string);

		$all_languages = eTranslateConfiguration::DefaultsAllLanguages();
		$locale = get_locale();

		$language = false;
		if (isset($all_languages[$language_string])) {
			$language = $all_languages[$language_string];
		}
		else {
			foreach ($all_languages as $locale => $try_language) {
				if ($try_language['allcaps'] == strtoupper($language_string)) {
					$language = $try_language;
					break;
				}
				if ($try_language['isocode'] == strtoupper($language_string)) {
					$language = $try_language;
					break;
				}
			}
		}

		if (!$language) {
			return false;
		}

		$language['label'] = $language['labels'][$locale];

		if ($output == 'assource') {
			return $language['assource'];
		}
		elseif ($output == 'astarget') {
			return $language['astarget'];
		}
		elseif ($output == 'isocode') {
			return $language['isocode'];
		}
		elseif ($output == 'full') {
			return $language;
		}
		elseif ($output == 'label') {
			return $language['label'];
		}
		else {
			return $language['isocode'];
		}
	}

	static function DefaultsMetaboxBehaviours() {
		$array = array(
			'replace' => __('Replace content', 'etranslate'),
			'append' => __('Append to content', 'etranslate')
		);
		return apply_filters(__METHOD__ , $array);
	}

	static function getFormalityLevel() {
		return apply_filters(__METHOD__, get_option('etranslate_default_formality'));
	}

	static function DefaultsISOCodes() {
		$locale = get_locale();
		$all_languages = eTranslateConfiguration::DefaultsAllLanguages();
		$languages = array();
		foreach ($all_languages as $isocode => $labels) {
			$languages[$isocode] = $labels['labels'][$locale];
		}
		return apply_filters(__METHOD__ , $languages);
	}

	static function DefaultsAllLanguages() {
		$languages = array();
		$csv_labels = 'locale,allcaps,isocode,assource,astarget,bg_BG,cs_CZ,da_DA,de_DE,el_GR,en_US,en_GB,es_ES,et_EE,fi_FI,fr_FR,hu_HU,it_IT,ja_JP,lt_LT,lv_LV,nl_NL,pl_PL,pt_PT,pt_BR,ro_RO,ru_RU,sk_SK,sl_SI,sv_SE,zh_CN
bg_BG,BG_BG,BG,BG,BG,Български,Bulharský,Bulgarsk,Bulgarisch,Βουλγαρική,Bulgarian,Bulgarian,Búlgaro,Bulgaaria,Bulgarian,Bulgare,Bolgár,Bulgaro,ブルガリア語,Bulgarų,Bulgāru,Bulgaars,Bułgarski,Búlgaro,Búlgaro,Bulgară,Болгарский,Bulharský,Bolgarski,Bulgariska,保加利亚人
cs_CZ,CS_CZ,CS,CS,CS,Чешки,Čeština,Tjekkisk,Tschechisch,Τσεχική,Czech,Czech,Checo,Tšehhi,Tšekki,Tchèque,Cseh,Ceco,チェコ語,čekų kalba,Čehu,Tsjechisch,czeski,Checo,Tcheco,Cehă,чешский,Český,Češčina,Tjeckiska,捷克
da_DA,DA_DA,DA,DA,DA,Датски,Dánština,dansk,Dänisch,Δανικά,Danish,Danish,Danés,Taani,tanska,Danois,dán,Danese,デンマーク語,Danų kalba,Dāņu,Deens,duński,Dinamarquês,Dinamarquês,Daneză,датский,Dánsky,danski,Danska,丹麦语
de_DE,DE_DE,DE,DE,DE,Немски,Němčina,tysk,Deutsch,Γερμανικά,German,German,Alemán,Saksa,saksa,Allemand,német,Tedesco,ドイツ語,Vokiečių kalba,Vācu,Duits,Niemiecki,Alemão,Alemão,Germană,немецкий язык,Nemčina,nemščina,Tyska,德国
el_GR,EL_GR,EL,EL,EL,Гръцки,Řečtina,Græsk,Griechisch,Ελληνικά,Greek,Greek,Griego,Kreeka,kreikka,Grec,görög,Greco,ギリシャ語,Graikų kalba,grieķu,Grieks,grecki,Grego,Grego,Greacă,греческий,Gréčtina,grščina,Grekiska,希腊语
en_US,EN_US,EN,EN,EN-US,Английски,Angličtina,engelsk,Englisch,Αγγλικά,English,English,Inglés,inglise,Englanti,Anglais,angol,Inglese,英語,Anglų,Angļu,Engels,angielski,Inglês,Inglês,Engleză,английский язык,Angličtina,angleščina,Engelska,英文
en_GB,EN_GB,EN,EN,EN-GB,Английски,Angličtina,engelsk,Englisch,Αγγλικά,English,English,Inglés,inglise,Englanti,Anglais,angol,Inglese,英語,Anglų,Angļu,Engels,angielski,Inglês,Inglês,Engleză,английский язык,Angličtina,angleščina,Engelska,英文
es_ES,ES_ES,ES,ES,ES,Испански,Španělština,spansk,Spanisch,Ισπανικά,Spanish,Spanish,Español,Hispaania,Espanja,Espagnol,Spanyol,Spagnolo,スペイン語,Ispanų kalba,Spāņu,Spaans,hiszpański,Espanhol,Espanhol,Spaniolă,испанский,Španielčina,Španščina,Spanska,西班牙语
et_EE,ET_EE,ET,ET,ET,Естонски,Estonština,Estisk,Estnisch,Εσθονικά,Estonian,Estonian,Estonio,Eesti keel,Viro,Estonien,Észt,Estone,エストニア語,Estų kalba,Igauņu,Ests,estoński,Estónio,Estoniano,Estoniană,эстонский,Estónčina,Estonščina,Estniska,爱沙尼亚语
fi_FI,FI_FI,FI,FI,FI,Финландски,Finština,finsk,Finnisch,Φινλανδικά,Finnish,Finnish,Finlandés,Soome,suomi,Finnois,finn,Finlandese,フィンランド語,Suomių kalba,Somu,Fins,fiński,Finlandês,Finlandês,Finlandeză,финский,Fínsky,finščina,Finska,芬兰
fr_FR,FR_FR,FR,FR,FR,Френски,Francouzština,Fransk,Französisch,Γαλλικά,French,French,Francés,prantsuse,ranska,Français,francia,Francese,フランス語,Prancūzų,Franču,Frans,Francuski,Francês,Francês,Franceză,французский,Francúzština,Francoski,Franska,法国人
hu_HU,HU_HU,HU,HU,HU,Унгарски,Maďarština,Ungarsk,Ungarisch,Ουγγρικά,Hungarian,Hungarian,Húngaro,Ungari,unkari,Hongrois,Magyar,Ungherese,ハンガリー語,vengrų,Ungāru,Hongaars,Węgierski,Húngaro,Húngaro,Maghiară,венгерский,Maďarčina,madžarski,Ungerska,匈牙利语
it_IT,IT_IT,IT,IT,IT,Италиански,italština,Italiensk,Italienisch,Ιταλικά,Italian,Italian,Italiano,Itaalia,italia,Italien,olasz,Italiano,イタリア語,italų kalba,Itāļu,Italiaans,włoski,Italiano,Italiano,Italiană,итальянский,taliansky,italijanščina,Italienska,意大利
ja_JP,JA_JP,JA,JA,JA,Японски,japonština,japansk,Japanisch,Ιαπωνικά,Japanese,Japanese,Japonés,jaapani,japani,Japonais,japán,Giapponese,日本語,japonų,japāņu,Japans,japoński,Japonês,Japonês,Japoneză,Японский,Japončina,japonščina,Japanska,日文
lt_LT,LT_LT,LT,LT,LT,Литовски,Litevština,Litauisk,Litauisch,Λιθουανικά,Lithuanian,Lithuanian,Lituano,Leedu,liettuan kieli,Lituanien,litván,Lituano,リトアニア語,Lietuvių,Lietuviešu,Litouws,Litewski,Lituano,Lituano,lituaniană,литовский,Litovčina,litovščina,Litauiska,立陶宛语
lv_LV,LV_LV,LV,LV,LV,Латвийски,lotyština,lettisk,Lettisch,Λετονία,Latvian,Latvian,Letón,Läti,Latvian,Letton,lett,Lettone,ラトビア語,Latvių,Latviešu,Lets,łotewski,Letão,Letão,Letonă,латышский язык,lotyština,latvijski,Lettiska,拉脱维亚人
nl_NL,NL_NL,NL,NL,NL,Нидерландски,nizozemština,hollandsk,Niederländisch,Ολλανδικά,Dutch,Dutch,Holandés,Hollandi,Hollanti,Néerlandais,holland,Olandese,オランダ語,Olandų,Holandiešu,Nederlands,niderlandzki,Holandês,Holandês,olandeză,голландский,Holandský,nizozemščina,Nederländska,荷兰语
pl_PL,PL_PL,PL,PL,PL,Полски,Polština,polsk,Polnisch,Πολωνικά,Polish,Polish,Polaco,Poola,Puola,Polonais,Lengyel,Polacco,ポーランド語,Lenkų kalba,Poļu,Pools,Polski,Polaco,Polonês,Poloneză,польский,Poľština,poljščina,Polska,波兰语
pt_PT,PT_PT,PT,PT,PT-PT,Португалски,Portugalština,portugisisk,Portugiesisch,Πορτογαλικά,Portuguese,Portuguese,Portugués,Portugali,Portugali,Portugais,Portugál,Portoghese,ポルトガル語,Portugalų,Portugāļu,Portugees,portugalski,Português,Português,Portugheză,Португальский,Portugalčina,portugalščina,Portugisiska,葡萄牙语
pt_BR,PT_BR,PT,PT,PT-BR,Португалски,Portugalština,portugisisk,Portugiesisch,Πορτογαλικά,Portuguese,Portuguese,Portugués,Portugali,Portugali,Portugais,Portugál,Portoghese,ポルトガル語,Portugalų,Portugāļu,Portugees,portugalski,Português,Português,Portugheză,Португальский,Portugalčina,portugalščina,Portugisiska,葡萄牙语
ro_RO,RO_RO,RO,RO,RO,Румънски,Rumunština,rumænsk,Rumänisch,Ρουμανικά,Romanian,Romanian,Rumano,Rumeenia,Romanian,Roumain,Román,Rumeno,ルーマニア語,Rumunų,Rumāņu,Roemeens,rumuński,Romeno,Romeno,Română,румынский язык,Rumunčina,romunščina,Rumänska,罗马尼亚语
ru_RU,RU_RU,RU,RU,RU,Руски,ruština,russisk,Russisch,Ρωσικά,Russian,Russian,Ruso,Vene,Venäjä,Russe,orosz,Russo,ロシア語,rusų,Krievu,Russisch,rosyjski,Russo,Russo,Rusă,русский,Ruský,ruščina,Ryska,俄语
sk_SK,SK_SK,SK,SK,SK,Словашки,Slovenština,Slovakisk,Slowakisch,Σλοβάκικα,Slovak,Slovak,Eslovaco,slovaki keel,slovakki,Slovaque,szlovák,Slovacco,スロバキア語,Slovakų,slovāku,Slowaaks,słowacki,Eslovaco,Eslovaco,Slovacă,словацкий,Slovensky,slovaščina,Slovakiska,斯洛伐克
sl_SI,SL_SI,SL,SL,SL,Словенски,slovinština,slovensk,Slowenisch,Σλοβένικα,Slovenian,Slovenian,Esloveno,Sloveenia,Slovenian,Slovène,szlovén,Sloveno,スロベニア語,slovėnų,slovēņu,Sloveens,słoweński,Esloveno,Esloveno,Slovenă,словенский,slovinčina,slovenski,Slovenska,斯洛文尼亚语
sv_SE,SV_SE,SV,SV,SV,Шведски,švédština,svensk,Schwedisch,Σουηδικά,Swedish,Swedish,Sueco,Rootsi,Ruotsi,Suédois,Svéd,Svedese,スウェーデン語,švedų,zviedru,Zweeds,szwedzki,Sueco,Sueco,Suedeză,шведский язык,švédsky,švedski,Svenska,瑞典语
zh_CN,ZH_CN,ZH,ZH,ZH,Китайски,Čínština,Kinesisk,Chinesisch,Κινέζικα,Chinese,Chinese,Chino,Hiina,Kiina,Chinois,Kínai,Cinese,中国語,Kinų kalba,Ķīniešu,Chinees,chiński,Chinês,Chinês,Chineză,китайский язык,Čínština,kitajščina,Kinesiska,中文';
		$csv_labels = explode( "\n", $csv_labels );
		$headers = explode(',', array_shift( $csv_labels ) );
		foreach( $csv_labels as $ligne ) {
			$labels = explode(',', $ligne );
			$labels = array_combine( $headers, $labels );
			
			$locale = $labels['locale'];
			unset( $labels['locale'] );
			
			$extra_country = false;
			if( strlen( $labels['astarget'] ) > 2  ) {
				$extra_country = substr( $labels['astarget'], 3, 2 );
			}

			$languages[$locale] = array();
			foreach( array('allcaps', 'assource', 'astarget', 'isocode' ) as $key ) {
				$languages[$locale][$key] = $labels[$key];
				unset( $labels[$key] );
			}


			if( $extra_country ) {
				foreach( $labels as $code => $label ) {
					$labels[$code] = $label . ' (' . $extra_country . ')';
				}
			}
			$languages[$locale]['labels'] = $labels;
		} 

		return apply_filters( __METHOD__, $languages );
	}

 // might serve somewhere
 	static function getContentTypes() {
		return apply_filters( __METHOD__, get_option('etranslate_contents_to_translate') );
	}
	static function getTargetLocales() {
		return apply_filters( __METHOD__, get_option( 'etranslate_target_locales') );
	}

	static function usingPolylang() {
		return function_exists( 'pll_the_languages' );
	}

	static function getBulkTargetLanguages() {
		return apply_filters( __METHOD__, get_option( 'etranslate_bulk_target_locales' ) );
	}	
}
