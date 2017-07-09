#iDoklad v2
PHP třída pro zasílání požadavků na iDoklad api v2.
[Dokumentace iDoklad api v2](https://app.idoklad.cz/Developer/Help)

## Vložení knihovny do projektu
Knihovnu vložíme do projektu naincludováním souboru src/iDoklad.php, nebo si knihovnu přidáme pomocí composeru. Následně se na knihovnu odkážeme pomocí use.
Zadáme naše client ID, client secret a v případě, že chceme použít OAuth2 autentifikaci i redirect URI. Nakonec si zavoláme objekt iDokladu, který zajišťuje veškerou komunikaci.
```php
include_once 'src/iDoklad.php';
            
use malcanek\iDoklad\iDoklad;
use malcanek\iDoklad\auth\iDokladCredentials;
use malcanek\iDoklad\iDokladException;

$clientId = 'Your client ID';
$clientSecret = 'Your client secret';
$redirectUri = 'Your redirect URI for OAuth2';

$iDoklad = new iDoklad($clientId, $clientSecret, $redirectUri);
```

## Autorizace pomocí OAuth2 - Authorization code flow
Autorizace pomocí OAuth probíhá v několika krocích. Jako client ID a client secret používáme údaje získané z developer portálu.

Nejdříve nabídneme uživateli URL adresu, kde zadá své přihlašovací údaje. Tu získáme pomocí nasledující metody:
```php
echo '<a href="'.$iDoklad->getAuthenticationUrl().'">Odkaz</a>';
```

Po zadání přihlašovacích údajů je uživatel přesměrován na zadanou redirect URI i s kódem, pomocí kterého získáme jeho credentials údaje.
Kód zpracujeme následujícím způsobem:
```php
$iDoklad->requestCredentials($_GET['code']);
```

Nyní jsou v instanci objektu založeny credentials a můžeme odesílat dotazy na iDoklad api. Credentials můžeme získat 2 způsoby.
Získání credentials přímo z objektu:
```php
$credentials = $iDoklad->getCredentials();
echo $credentials->toJson();
```

###Zpracování credentials pomocí credentials callbacku:
Callback funguje tak, že knihovna zavolá callback funkci vždy, když jsou změněny credentials. To se hodí, jelikož automaticky probíhá refresh tokenu po jeho vypršení.
```php
$iDoklad->setCredentialsCallback(function($credentials){
    file_put_contents('credentials.json', $credentials->toJson());
});
```

###Nahrání credentials do iDoklad objektu
Založení objektu s již existujícími credentials
```php
$iDoklad = new iDoklad($clientId, $clientSecret, $redirectUri, $credentials);
```

Vložení credentials do již existujícího objektu
```php
$iDoklad->setCredentials($credentials);
```

Poté co objekt obsahuje credentials, lze provádět dotazy do iDoklad api.

##Autorizace pomocí OAuth2 - Client credentials flow
Tato metoda je jednodušší. Credentials získáme na základě client id a client secret, které získáme z nastavení účtu uživatele.
Po založení objektu pouze zavoláme:
```php
$iDoklad->authCCF();
```

Jako u OAuth2 - Authorization code flow i zde funguje credentials callback.

##Odesílání požadavků na iDoklad api
Pro odeslání požadavku na api slouží iDokladRequest objekt. Ten lze v nejjednodušší podobě založit pouze s jedním parametrem, který určuje akci dle dokumentace, a poté rovnou odeslat na api.
```php
$request = new iDokladRequest('IssuedInvoices');
$response = $iDoklad->sendRequest($request);
```

##Získání dat z api
Pokud požadavek proběhne úspěšně, jsou zpět vrácena data v podobě iDokladResponse objektu. Nejdříve zkontrolujeme, zda požadavek proběhl v pořádku (návratová hodnota by měla být 200):
```php
$response->getCode();
```

Poté můžeme získat samotná data v poli:
```php
$response->getData();
```

##Odchytávání chyb
Třída vyhazuje vyjímky typu iDokladException.

##Vytvoření nové faktury
```php
$request->addMethodType('POST');
$data = array(
    'PurchaserId' => 3739927,
    'IssuedInvoiceItems' => [array(
        'Name' => 'Testovaci polozka',
        'UnitPrice' => 100,
        'Amount' => 1
    )]
);
$request->addPostParameters($data);
```

##Použití filtru a třídění
Pro použití filtru použijeme třídu iDokladFilter. Parametry můžeme zadat hned při založení třídy, první parametr je jméno pole, které chceme filtrovat, druhý parametr je operátor, poslední parametr je hodnota.
```php
$filter = new iDokladFilter('DocumentNumber', '==', '20170013');
$request->addFilter($filter);
```

Filtrů můžeme přidat několik zároveň a poté můžeme zvolit vztah mezi filtry, aby platili všechny zároveň (and), nebo alespoň jeden (or).
```php
$request->setFilterType('or');
```

Pro použití třídění použijeme třídu iDokladSort. Opět můžeme hned přidávat parametry, kdy první parametr je jméno pole a druhý parametr je dobrovolný a lze zadat, zda řadit vzestupně (asc) či sestupně (desc).
```php
$sort = new iDokladSort('DocumentNumber', 'desc');
$request->addSort($sort);
```

##Stránkování a počet vrácených položek
```php
$request->setPage(2);
$request->setPageSize(5);
```

##Jiné úpravy
Pokud potřebujeme použít metody POST, PUT, PATCH, DELETE, použijeme k tomu metodu addMethodType nad objektem iDokladRequest.

##Příklady
Příklady použití lze vidět v souborech acf.php a ccf.php. acf.php obsahuje příklad použití authorization code flow, ccf obsahuje příklad na client credentials flow, stačí doplnit vlastní client ID, client secret a redirect URI.