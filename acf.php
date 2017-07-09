<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
            include_once 'src/iDoklad.php';
            
            use malcanek\iDoklad\iDoklad;
            use malcanek\iDoklad\auth\iDokladCredentials;
            use malcanek\iDoklad\iDokladException;
            
            $clientId = 'Your client ID';
            $clientSecret = 'Your client secret from developer portal';
            $redirectUri = 'Your redirect URI from developer portal';
            
            try{
                $iDoklad = new iDoklad($clientId, $clientSecret, $redirectUri);
                $iDoklad->setCredentialsCallback(function($credentials){
                    file_put_contents('credentials.json', $credentials->toJson());
                });
                if(!file_exists('credentials.json') && empty($_GET['code'])){
                    echo '<a href="'.$iDoklad->getAuthenticationUrl().'">Click</a>';
                } else {
                    if(!empty($_GET['code'])){
                        $iDoklad->requestCredentials($_GET['code']);
                    } else {
                        $credentials = new iDokladCredentials(file_get_contents('credentials.json'), true);
                        $iDoklad->setCredentials($credentials);
                    }
                    $request = new \malcanek\iDoklad\request\iDokladRequest('IssuedInvoices');
                    $response = $iDoklad->sendRequest($request);
                    echo '<pre>';
                    print_r($response);
                    echo '</pre>';
                }
            } catch (iDokladException $ex) {
                echo $ex->getMessage();
                echo $ex->getTraceAsString();
            }
        ?>
    </body>
</html>
