```php
QRCode::generate('Hello world!', 400)->show();
```

```php
$qrcode = new QRCode('Hello world!', 400);
$qrcode->generate();
$qrcode->show();
```