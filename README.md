# Ishlatish bo'yicha yo'riqnoma

Laravel Requestdan olish uchun:
```php
StoreIncomeActionData::createFromRequest($request);
```
Arraydan olish uchun
```php
StoreIncomeActionData::createFromArray($array);
```
Agar siz yarim yo'lda validatsiya qo'shmoqchi bo'lsangiz:
```php
$actionData->addValidationRule('id', 'required|integer');
//Qayta yuklash uchun
$actionData->validateException();
```

Aytaylik sizda data degan narsa array tipida. Siz unga o`zingiz qiymat qo'shishingiz ham mumkin. Masalan:
```php
$userId = Auth::id();
$actionData->set('data', '*.user_id', $userId);
$actionData->addValidationRule('data.*.user_id', 'required|integer');
$actionData->validateException();
```
ActionData ni arrayga o`girish uchun:
```php
$actionData->toArray();
// or  
$actionData->all();
// or
$actionData->toSnakeArray();
```
Siza o`sha classdan faqat bazilari kerak bo`lsa
```php
$actionData->only(['id', 'name']);
```
