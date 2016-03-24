# offer_balance

balance and history API


Get balance
    Get balances for all users

    Method:     GET
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

    Method:     GET
    URI:        http://domain/balance/<user_id>
                user_id - integer
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
    Method:     POST
    URI:        http://domain/user
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

    Method:     DELETE
    URI:        http://domain/balance_delete_user/<user_id>
                user_id - integer
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

Add/sub user balance
If trans_desc is empty - method generates a md5 hash for transaction

    Method:     PUT (x-www-form-urlencoded)
    URI:        http://domain/balance/<user_id>
                user_id - integer
    Example:    http://domain/balance/12
    Params:     ['operation' - string ('add', 'sub'), 'balance' - integer or decimal(19.2), 'trans_desc' - string]
    Example:    ['operation' = 'add', 'balance' = 32.40, 'trans_desc' = 'add cash from card #e322m234mfz']
    Response:
                {
                  "user_id": 12,
                  "balance": "32.40"
                }
    Exceptions: Code => description
                503 => 'Failed to add/sub balance to user'
                504 => 'Operation type mismatch. Failed to add/sub balance to user'
                505 => 'User has not enough money'
                507 => 'User ID format is not correct'
                508 => 'balance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving balance history'
    Log:
                id  timastamp           uid sum     operation       transaction_description             user_to
                81	2016-03-21 23:23:54	12	32.40	balance_add	add cash from card #e322m234mfz	    0


Transfer balance from user to user
Transfer sum from user to user balance. If trans_desc is empty - method generates a md5 hash for transaction

    Method:     PUT
    URI:        http://domain/transfer
    Example:    -
    Params:     ['sender_id' - integer, 'balance' - integer or decimal(19.2), 'receiver_id' - integer, 'trans_desc' - string]
    Example:    ['sender_id' = 12, 'balance' = 1.40, 'receiver_id' = 3, 'trans_desc' = 'f5ceded9f1a974ba98bb8e90fa9d5c22']
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
                503 => 'Failed to add/sub balance to user'
                504 => 'Operation type mismatch. Failed to add/sub balance to user'
                505 => 'User has not enough money'
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