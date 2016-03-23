# offer_balance


**TODO:** 

* Сменить balance на balance (с одной L).
* Привести все ошибки в соответствии с [HTTP статусами](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes).
* (Это не отменяет того, что внутри можно использовать свои коды ошибок и складывать их в лог).
* Убрать POST из всех роутов, где он не нужен. POST используется только для создания/добавления сущностей.




balance and history API


Get balance
    Get balances for all users

    Method:     GET,POST
    URI:        http://domain/balance
    Example:    http://domain/balance
    Params:     []
    Response:
                [
                  {
                    "user_id": 2,
                    "balance": "85.10"
                  },
                  {
                    "user_id": 3,
                    "balance": "10476.00"
                  },
                  {
                    "user_id": 7,
                    "balance": "0.00"
                  },
                  {
                    "user_id": 44,
                    "balance": "44.44"
                  }
                ]

Get balance for user
Shows user balance item if user exists

    Method:     GET,POST
    URI:        http://domain/balance/user_id
    Example:    http://domain/balance/2
    Params:     []
    Response:
                {
                  "user_id": 2,
                  "balance": "85.10"
                }
    Exceptions: Code => description
                509 => 'User does not exist'

Add user

**TODO**: Добавление пользователя должно выглядеть как POST запрос на адрес `http://domain/users`.

    Method:     POST
    URI:        http://domain/balance_add_user
    Example:    -
    Params:     ['uid' = integer]
    Example:    ['uid' = 15]
    Response:
                {
                  "user_id": 15,
                  "balance": "0.00"
                }
    Exceptions: Code => description
                501 => 'Failed to add  new user'
                507 => 'User ID format is not correct'
                512 => 'Failed saving balance history'

Delete user
Delete user if his balance is zero otherwise delete will be cancelled

**TODO**: Должен быть как HTTP method DELETE на `http://domain/user/<user_id>`.

    Method:     DELETE
    URI:        http://domain/balance_delete_user/user_id
    Example:    http://domain/balance_delete_user/12
    Params:     []
    Example:    []
    Response:
                {
                  "code": 0,
                  "message": "User deleted"
                }
    Exceptions: Code => description
                502 => 'Delete user failed'
                507 => 'User ID format is not correct'
                509 => 'User does not exist'
                511 => 'Delete user failed - non zero balance'
                512 => 'Failed saving balance history'

Add balance to user
Add sum to user balance. If trans_desc is empty - method generates a md5 hash for transaction

**TODO**: Имхо, должно быть в стиле: 

```HTTP PUT http://domain/balance/<user_id>```

    Method:     POST
    URI:        http://domain/balance_add
    Example:    -
    Params:     ['uid' - integer, 'balance' - integer or decimal(19.2), 'trans_desc' - string]
    Example:    ['uid' = 12, 'balance' = 32.40, 'trans_desc' = 'add cash from card #e322m234mfz']
    Response:
                {
                  "user_id": 12,
                  "balance": "32.40"
                }
    Exceptions: Code => description
                503 => 'Failed to add balance to user'
                507 => 'User ID format is not correct'
                508 => 'balance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving balance history'
    Log:
                id  timastamp           uid sum     operation       transaction_description             user_to
                81	2016-03-21 23:23:54	12	32.40	balance_add	add cash from card #e322m234mfz	    0

Sub user balance
Sub sum of user balance. If trans_desc is empty - method generates a md5 hash for transaction

**TODO**: То же самое, что и выше. Как вариант, можно передавать конкретный тип операции (add (добавление), sub(вычитание), set(установление)) баланса в отдельный параметр. 

Например, :

```
HTTP PUT http://domain/balance/<user_id>

action: sub
balance: <number>
comment: 'Оло-ло-ло'
```

    Method:     POST
    URI:        http://domain/balance_sub
    Example:    -
    Params:     ['uid' - integer, 'balance' - integer or decimal(19.2), 'trans_desc' - string]
    Example:    ['uid' = 12, 'balance' = 12.40, 'trans_desc' = 'get cash via terminal #e322m234mfz']
    Response:
                {
                  "user_id": 12,
                  "balance": "20.00"
                }
    Exceptions: Code => description
                504 => 'Failed to sub balance to user'
                507 => 'User ID format is not correct'
                508 => 'balance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving balance history'
    Log:
                id  timastamp           uid sum     operation       transaction_description             user_to
                81	2016-03-21 23:23:54	12	12.40	balance_sub	get cash via terminal #e322m234mfz	0

Transfer balance from user to user
Transfer sum from user to user balance. If trans_desc is empty - method generates a md5 hash for transaction

**TODO** (необязательно): можно тогда просто убрать слово "балансе", чтобы роутинг выглядел как `http://domain/transfer` - у нас так и так весь сервис про баланс.


    Method:     POST
    URI:        http://domain/balance_transfer
    Example:    -
    Params:     ['uid' - integer, 'balance' - integer or decimal(19.2), 'uid2' - integer, 'trans_desc' - string]
    Example:    ['uid' = 12, 'balance' = 1.40, 'uid2' = 3, 'trans_desc' = 'f5ceded9f1a974ba98bb8e90fa9d5c22']
    Response:
                [
                  {
                    "user_id": 3,
                    "balance": "10476.40"
                  },
                  {
                    "user_id": 12,
                    "balance": "18.60"
                  }
                ]
    Exceptions: Code => description
                503 => 'Failed to add balance to user'
                504 => 'Failed to sub balance to user'
                506 => 'Transfer from user to user failed'
                507 => 'User ID format is not correct'
                508 => 'balance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving balance history'
    Log:
                id  timastamp           uid sum     operation           transaction_description             user_to
                81	2016-03-21 23:23:54	12	1.40	balance_transfer	f5ceded9f1a974ba98bb8e90fa9d5c22	3

Get balance history


    Method:     GET
    URI:        http://domain/history/uid=integer&date-start=YYYY-MM-DD&date-end=YYYY-MM-DD
    Example:    http://domain/history/uid=12&date-start=2016-03-20&date-end=2016-03-22
                http://domain/history/date-start=2016-03-20&date-end=2016-03-22
                http://domain/history/uid=12&date-start=2016-03-20
                http://domain/history/date-start=2016-03-20
                http://domain/history/uid=12
                http://domain/history
    Params:     []
    Response:
                [
                  {
                    "id": 1,
                    "date": "2016-03-22 18:26:23",
                    "receiver_id": 12,
                    "sum": "0.00",
                    "operation": "user_add",
                    "transaction_description": "ddacd426f3cb0ce647df33fb8364c8f2",
                    "sender_id": 0
                  },
                  {
                    "id": 2,
                    "date": "2016-03-20 18:26:41",
                    "receiver_id": 54,
                    "sum": "0.00",
                    "operation": "user_add",
                    "transaction_description": "3020bc649751b1dd930f4dc4e947e2f3",
                    "sender_id": 0
                  }
                ]