/**
 * this is for testing the system directly in the browser
 */
const STUBS = [
{
    url: '/fake-ajax-url/crop_thumbnails/v1/crop?imageId=123&posttype=',
    method: 'GET',
    content:
        {
        options: {
            "hide_post_type": { "custom_css": "1", "customize_changeset": "1", "wpcf7_contact_form": "1" },
            "hide_size": {
                "post": { "thumbnail": "1", "mitarbeiter": "1" },
                "page": { "thumbnail": "1", "mitarbeiter": "1" },
                "mitarbeiter": { "thumbnail": "1", "post-thumbnail": "1", "small-thumb": "1" },
                "angebote": { "mitarbeiter": "1" }
            }
        },
        sourceImageId: 123,
        sourceImage: {
            "original_image": {
                "url": "/testimages/testimage.jpeg",
                "width": 5472, "height": 3648, "gcd": "1824", "ratio": 1.5, "printRatio": "3:2", "image_size": "original_image"
            },
            "full": {
                "url": "/testimages/testimage-scaled.jpeg",
                "width": 1920, "height": 1280, "gcd": "640", "ratio": 1.5, "printRatio": "3:2", "image_size": "full"
            },
            "large": {
                "url": "/testimages/testimage-1024x683.jpeg",
                "width": 1024, "height": 683, "gcd": "1", "ratio": 1.499267935578331, "printRatio": "1024:683", "image_size": "large"
            },
            "medium_large": {
                "url": "/testimages/testimage-768x512.jpeg",
                "width": 768, "height": 512, "gcd": "256", "ratio": 1.5, "printRatio": "3:2", "image_size": "medium_large"
            }
        },
        sourceImageMeta: { "aperture": "0", "credit": "", "camera": "", "caption": "", "created_timestamp": "0", "copyright": "", "focal_length": "0", "iso": "0", "shutter_speed": "0", "title": "", "orientation": "0", "keywords": [] },
        postTypeFilter: null,
        imageSizes: [
            {
                "name": "thumbnail", "nameLabel": "thumbnail",
                "url": "/testimages/testimage-200x200.jpeg",
                "width": 200, "height": 200, "gcd": "200", "ratio": 1, "printRatio": "1:1", "hideByPostType": false, "crop": true
            },
            {
                "name": "post-thumbnail", "nameLabel": "post-thumbnail",
                "url": "/testimages/testimage-625x275.jpeg",
                "width": 625, "height": 275, "gcd": "25", "ratio": 2.272727272727273, "printRatio": "25:11", "hideByPostType": false, "crop": true
            },
            {
                "name": "small-thumb", "nameLabel": "small-thumb",
                "url": "/testimages/testimage-250x140.jpeg",
                "width": 250, "height": 140, "gcd": "10", "ratio": 1.7857142857142858, "printRatio": "25:14", "hideByPostType": false, "crop": true
            },
            {
                "name": "medium-thumb", "nameLabel": "medium-thumb",
                "url": "/testimages/testimage-500x280.jpeg",
                "width": 500, "height": 280, "gcd": "20", "ratio": 1.7857142857142858, "printRatio": "25:14", "hideByPostType": false, "crop": true
            },
            {
                "name": "mitarbeiter", "nameLabel": "mitarbeiter",
                "url": "/testimages/testimage-450x300.jpeg",
                "width": 450, "height": 300, "gcd": "150", "ratio": 1.5, "printRatio": "3:2", "hideByPostType": false, "crop": true
            },
            {
                "name": "issue-94", "nameLabel": "issue-94",
                "url": "testimages/testimage-3840x2160.jpeg",
                "width": 3840, "height": 2160, "gcd": "240", "ratio": 1.7777777777777777, "printRatio": "16:9", "hideByPostType": false, "crop": true
            }
        ],
        lang: {
            "warningOriginalToSmall": "Warung: das Original-Bild ist zu klein um es f\u00fcr diese Thumbnail-Gr\u00f6\u00dfe in guter Qualit\u00e4t zuzuschneiden.",
            "cropDisabled": "Das Zuschneiden ist f\u00fcr diesen Eintragstyp deaktiviert.",
            "waiting": "Bitte warten Sie bis die Bilder zugeschnitten wurden.",
            "rawImage": "Original-Bild",
            "pixel": "Pixel",
            "instructions_overlay_text": "W\u00e4hlen Sie eine Bildgr\u00f6\u00dfe aus.",
            "instructions_header": "Schnell-Anleitung",
            "instructions_step_1": "Schritt 1: W\u00e4hlen Sie eine Bildgr\u00f6\u00dfe aus der Liste.",
            "instructions_step_2": "Schritt 2: \u00c4ndern Sie den Auswahlrahmen im obigen Bild.",
            "instructions_step_3": "Schritt 3: Klicken Sie auf \"Zuschnitt \u00fcbernehmen\".",
            "label_crop": "Zuschnitt \u00fcbernehmen",
            "label_same_ratio_mode": "Bilder mit gleichem Seitenverh\u00e4ltnis",
            "label_same_ratio_mode_nothing": "nichts tun",
            "label_same_ratio_mode_select": "gemeinsam ausw\u00e4hlen",
            "label_same_ratio_mode_group": "gruppieren",
            "label_deselect_all": "nichts ausw\u00e4hlen",
            "label_large_handles": "gro\u00dfe Kontrollfl\u00e4chen verwenden",
            "dimensions": "Gr\u00f6\u00dfe:",
            "ratio": "Seitenverh\u00e4ltnis:",
            "cropped": "zugeschnitten",
            "lowResWarning": "Original-Bild ist zu klein f\u00fcr guten Bildzuschnitt!",
            "notYetCropped": "Wurde bisher nicht von WordPress zugeschnitten.",
            'label_use_original_image' : 'Use the real original image',
            'info_use_original_image' : 'As your original uploaded image was quit big, Wordpress has generates an scaled image. You may use the real original image to get the best quality.',
            "message_image_orientation": "Dieses Bild nutzt eine Bild-Rotation in seinen EXIF-Metadaten. Beachten Sie, dass das zu gedrehten oder gespiegelten Bildern beim Safari-Browser (IPad, IPhone) f\u00fchren kann.",
            "script_connection_error": "Fehler beim Verbindungsaufbau zum Server.",
            "noPermission": "Es ist dir nicht gestattet, die Miniaturansichten zuzuschneiden.",
            "infoNoImageSizesAvailable": "Keine Bildgr\u00f6\u00dfen f\u00fcr den Bild-Zuschnitt verf\u00fcgbar.",
            "headline_selected_image_sizes": "Ausgew\u00e4hlte Bildgr\u00f6\u00dfen"
        },
        nonce: "abc123",
        hiddenOnPostType: false
    }
},
{
    url: '/fake-ajax-url/crop_thumbnails/v1/settings',
    method: 'GET',
    content:{
        "options": {
            "hide_post_type": { "custom_css": "1", "customize_changeset": "1", "wpcf7_contact_form": "1" },
            "hide_size": {
                "post": { "thumbnail": "1", "mitarbeiter": "1" },
                "page": { "thumbnail": "1", "mitarbeiter": "1" },
                "mitarbeiter": { "thumbnail": "1", "post-thumbnail": "1", "small-thumb": "1" },
                "angebote": { "mitarbeiter": "1" }
            },
        },
        "post_types": {
            "post": { "name": "post", "label": "Beiträge", "description": "", "public": true },
            "page": { "name": "page", "label": "Seiten", "description": "", "public": true },
            "custom_css": { "name": "custom_css", "label": "Individuelles CSS", "description": "", "public": false },
            "customize_changeset": { "name": "customize_changeset", "label": "Änderungs-Sets", "description": "", "public": false },
            "oembed_cache": { "name": "oembed_cache", "label": "oEmbed-Antworten", "description": "", "public": false },
            "user_request": { "name": "user_request", "label": "Benutzer-Anfragen", "description": "", "public": false },
            "wp_block": { "name": "wp_block", "label": "Wiederverwendbare Blöcke", "description": "", "public": false },
            "wp_template": { "name": "wp_template", "label": "Templates", "description": "Templates, die in dein Theme eingefügt werden können.", "public": false },
            "wp_template_part": { "name": "wp_template_part", "label": "Template-Teile", "description": "Template-Teile zum Einfügen in dein Template.", "public": false },
            "wp_global_styles": { "name": "wp_global_styles", "label": "Globale Stile", "description": "Globale Stile für die Verwendung in Themes.", "public": false },
            "wp_navigation": { "name": "wp_navigation", "label": "Navigationsmenüs", "description": "Navigationsmenüs, die in deine Website eingefügt werden können.", "public": false },
            "e-landing-page": { "name": "e-landing-page", "label": "Startseiten", "description": "", "public": true },
            "elementor_library": { "name": "elementor_library", "label": "Meine Templates", "description": "", "public": true },
            "wpcf7_contact_form": { "name": "wpcf7_contact_form", "label": "Kontaktformulare", "description": "", "public": true },
            "tm_playlist": { "name": "tm_playlist", "label": "Playlist", "description": "", "public": true },
            "tm_playlistentry": { "name": "tm_playlistentry", "label": "Playlist-Eintrag", "description": "", "public": false },
            "lkgaudio": { "name": "lkgaudio", "label": "Audio-Mitschnitt", "description": "", "public": false },
            "mitarbeiter": { "name": "mitarbeiter", "label": "Mitarbeiter", "description": "", "public": true },
            "angebote": { "name": "angebote", "label": "Angebote", "description": "", "public": true },
            "tmadditionalcontent": { "name": "tmadditionalcontent", "label": "Startseitenbild", "description": "", "public": false },
            "material": { "name": "material", "label": "Material", "description": "", "public": false }
        },
        "image_sizes": {
            "thumbnail": { "width": 200, "height": 200, "crop": true, "name": "thumbnail", "id": "thumbnail" },
            "medium": { "width": 600, "height": 600, "crop": false, "name": "medium", "id": "medium" },
            "medium_large": { "width": 768, "height": 0, "crop": false, "name": "medium_large", "id": "medium_large" },
            "large": { "width": 1024, "height": 1024, "crop": false, "name": "large", "id": "large" },
            "1536x1536": { "width": 1536, "height": 1536, "crop": false, "name": "1536x1536", "id": "1536x1536" },
            "2048x2048": { "width": 2048, "height": 2048, "crop": false, "name": "2048x2048", "id": "2048x2048" },
            "post-thumbnail": { "width": 625, "height": 275, "crop": true, "name": "post-thumbnail", "id": "post-thumbnail" },
            "small-thumb": { "width": 250, "height": 140, "crop": true, "name": "small-thumb", "id": "small-thumb" },
            "medium-thumb": { "width": 500, "height": 280, "crop": true, "name": "medium-thumb", "id": "medium-thumb" },
            "mitarbeiter": { "width": 450, "height": 300, "crop": true, "name": "mitarbeiter", "id": "mitarbeiter" }
        },
        "lang": {
            "general": {
                "save_changes": "Änderungen speichern",
                "successful_saved": "Successful saved",
                "nav_post_types": "Größen und Eintragstypen",
                "nav_plugin_test": "Plugin Test",
                "nav_developer_settings": "Enwickler-Einstellungen",
                "nav_user_permissions": "Benutzerberechtigung"
            },
            "user_permissions": {
                "text": "Wenn aktiv, können nur Benutzer, die in der Lage sind, Dateien zu bearbeiten, Miniaturansichten zuschneiden. Ansonsten (Standard) kann jeder Benutzer, der Dateien hochladen kann, auch Miniaturansichten zuschneiden."
            },
            "posttype_settings": {
                "intro_1": "Crop Thumbnails wurde erstellt um das Zuschneiden von Bildern für den Benutzer zu erleichtern. Oft muss der Nutzer nur eine Bildgröße zuschneiden, abhängig vom Typ des Eintrags. Das System (Wordpress) wird aber immer alle Größen erstellen. Hier können Sie auswählen für welche Eintragstypen welche Bildgrößen in der Plugin-Oberfläche angezeigt werden sollen.",
                "intro_2": "Crop Thumbnails wird nur Bilder anzeigen die einen Zuschnitt besitzen. Bildgrößen ohne Zuschnitt werden immer ausgeblendet.",
                "choose_image_sizes": "Wählen Sie die Bildgrößen, die Sie nicht anzeigen möchten, wenn der Benutzer den Button unter dem Beitragsbild verwendet.",
                "hide_on_post_type": "Crop-Thumbnail-Button unter dem Beitragsbild ausblenden?"
            },
            "developer_settings": {
                "enable_debug_js": "JS-Debug einschalten",
                "enable_debug_data": "Daten-Debug einschalten"
            },
            'paypal_info': {
                'headline' : 'Support the plugin author',
                'text' : 'You can support the plugin author (and let him know you love this plugin) by donating via Paypal. Thanks a lot!'
            }
        }
    }
}
];


export default (proxyRes, req, res) => {
  const matchingStub = STUBS.find(stub => req.url.includes(stub.url) && req.method === stub.method);
  if(matchingStub) {
    res.statusCode = matchingStub.statusCode ? matchingStub.statusCode : 201;//custom statusCode
    console.log('send stub data on', req.method, req.url, res.statusCode);
    res.writeHead(res.statusCode, { 'Content-Type': 'application/json' });
    res.write.call(res, JSON.stringify( matchingStub.content ));
    res.end();
  } else {
    res.writeHead(res.statusCode, { 'Content-Type': 'application/json' });
    res.write.call(res, JSON.stringify( { 'error' : 'stub do not found an target' } ));
    console.log('stub do not found an target');
    res.end();
  }
}