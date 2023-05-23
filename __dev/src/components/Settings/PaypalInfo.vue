<template>
    <div class="cpt_PaypalInfo">
        <h3>{{settings.lang.paypal_info.headline}}</h3>
        <p>{{settings.lang.paypal_info.text}}</p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
            <input type="hidden" name="cmd" value="_donations">
            <input type="hidden" name="business" value="volkmar.kantor@gmx.de">
            <input type="hidden" name="lc" value="DE">
            <input type="hidden" name="item_name" value="Volkmar Kantor - totalmedial.de">
            <input type="hidden" name="item_number" value="crop-thumbnails">
            <input type="hidden" name="no_note" value="0">
            <input type="hidden" name="currency_code" value="EUR">
            <input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
            <input type="image" src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">
            <img alt="" border="0" src="https://www.paypalobjects.com/de_DE/i/scr/pixel.gif" width="1" height="1">
        </form>
    </div>
</template>

<script>
export default {
    props: {
        settings: { required:true, type:Object },
    },
}
</script>

<style lang="scss">
.cpt_PaypalInfo { border:1px solid #298CBA; border-radius:3px; background-color:#f6f6f6; max-width:30em; padding:0 0.5em; margin:2em 0; text-align:center; }
</style>