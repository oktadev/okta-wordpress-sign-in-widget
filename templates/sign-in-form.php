<!-- to load Okta sign-in widget-->
<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/js/okta-sign-in.min.js" type="text/javascript"></script>
<script src="https://ok1static.oktacdn.com/assets/js/sdk/okta-auth-js/1.13.0/okta-auth-js.min.js" type="text/javascript"></script>
<!-- NTR: to make that ajax call to check if there is an existing okta cookie-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<link href="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/css/okta-sign-in.min.css" type="text/css" rel="stylesheet"/>
<link href="https://ok1static.oktacdn.com/assets/js/sdk/okta-signin-widget/2.6.0/css/okta-theme.css" type="text/css" rel="stylesheet"/>

<div id="primary" class="content-area">
  <div id="widget-container"></div>
</div>
<script>
    function doGetWithoutPrompt() {
        //check if there is an existing Okta cookie
        var authClient = new OktaAuth({
            url: '<?= OKTA_BASE_URL ?>',
            clientId: '<?= OKTA_CLIENT_ID ?>',
            redirectUri: '<?= wp_login_url() ?>',
            issuer: '<?= OKTA_BASE_URL ?>/oauth2/<?= OKTA_AUTH_SERVER_ID ?>',
        });

        authClient.token.getWithoutPrompt({
            responseType: ['id_token', 'token'],
            scopes: ['openid', 'email']
        })
        .then(function(tokens){
            showApp(tokens);
        })
        .then(function(err){
            console.log(err);
        })

    }

    function showApp(res) {
        var key = '';
        if (res[0]) {
            //id_token
            key = Object.keys(res[0])[0];
            signIn.tokenManager.add(key, res[0]);
        }
        get_profile(key, signIn.tokenManager.get(key));
    }

    function get_profile(token_type, token) {
        window.location.href = '<?php echo wp_login_url() ?>' + '?id_token=' + token.idToken;
    }

    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    };

    var signIn = new OktaSignIn({
        baseUrl: '<?php echo OKTA_BASE_URL ?>',
        clientId: '<?php echo OKTA_CLIENT_ID ?>',
        redirectUri: '<?php echo wp_login_url() ?>',
        authParams: {
            issuer: '<?= OKTA_BASE_URL ?>/oauth2/<?= OKTA_AUTH_SERVER_ID ?>',
            responseType: 'code',
            display: 'page',
            scopes: ['openid', 'email']
            // state: TODO
        }
    });
    signIn.session.get(function(res) {
        if (res.status==='ACTIVE') {
            doGetWithoutPrompt();
        } else {
            signIn.renderEl({
                    el: '#widget-container'
                },
                function success(res) {}
            );
        }
    });
</script>
