# offer_ballance


Ballance and history API


Get ballance
    Get balances for all users

    Method:     GET,POST
    URI:        http://domain/ballance
    Exemple:    http://domain/ballance
    Params:     []
    Response:
                [
                  {
                    "user_id": 2,
                    "ballance": "85.10"
                  },
                  {
                    "user_id": 3,
                    "ballance": "10476.00"
                  },
                  {
                    "user_id": 7,
                    "ballance": "0.00"
                  },
                  {
                    "user_id": 44,
                    "ballance": "44.44"
                  }
                ]

Get ballance for user
    Shows user ballance item if user exists

    Method:     GET,POST
    URI:        http://domain/ballance/user_id
    Exemple:    http://domain/ballance/2
    Params:     []
    Response:
                {
                  "user_id": 2,
                  "ballance": "85.10"
                }
    Exceptions: Code => description
                509 => 'User does not exist'

Add user

    Method:     POST
    URI:        http://domain/ballance_add_user
    Exemple:    -
    Params:     ['uid' = integer]
    Exemple:    ['uid' = 15]
    Response:
                {
                  "user_id": 15,
                  "ballance": "0.00"
                }
    Exceptions: Code => description
                501 => 'Failed to add  new user'
                507 => 'User ID format is not correct'
                512 => 'Failed saving ballance history'

Delete user
    Delete user if his ballance is zero otherwise delete will be cancelled

    Method:     DELETE
    URI:        http://domain/ballance_delete_user/user_id
    Exemple:    http://domain/ballance_delete_user/12
    Params:     []
    Exemple:    []
    Response:
                {
                  "code": 0,
                  "message": "User deleted"
                }
    Exceptions: Code => description
                502 => 'Delete user failed'
                507 => 'User ID format is not correct'
                509 => 'User does not exist'
                511 => 'Delete user failed - non zero ballance'
                512 => 'Failed saving ballance history'

Add ballance to user
    Add sum to user ballance. If trans_desc is empty - method generates a md5 hash for transaction

    Method:     POST
    URI:        http://domain/ballance_add
    Exemple:    -
    Params:     ['uid' - integer, 'ballance' - integer or decimal(19.2), 'trans_desc' - string]
    Exemple:    ['uid' = 12, 'ballance' = 32.40, 'trans_desc' = 'add cash from card #e322m234mfz']
    Response:
                {
                  "user_id": 12,
                  "ballance": "32.40"
                }
    Exceptions: Code => description
                503 => 'Failed to add ballance to user'
                507 => 'User ID format is not correct'
                508 => 'Ballance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving ballance history'
    Log:
                id  timastamp           uid sum     operation       transaction_description             user_to
                81	2016-03-21 23:23:54	12	32.40	ballance_add	add cash from card #e322m234mfz	    0

Sub user ballance
    Sub sum of user ballance. If trans_desc is empty - method generates a md5 hash for transaction

    Method:     POST
    URI:        http://domain/ballance_sub
    Exemple:    -
    Params:     ['uid' - integer, 'ballance' - integer or decimal(19.2), 'trans_desc' - string]
    Exemple:    ['uid' = 12, 'ballance' = 12.40, 'trans_desc' = 'get cash via terminal #e322m234mfz']
    Response:
                {
                  "user_id": 12,
                  "ballance": "20.00"
                }
    Exceptions: Code => description
                504 => 'Failed to sub ballance to user'
                507 => 'User ID format is not correct'
                508 => 'Ballance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving ballance history'
    Log:
                id  timastamp           uid sum     operation       transaction_description             user_to
                81	2016-03-21 23:23:54	12	12.40	ballance_sub	get cash via terminal #e322m234mfz	0

Transfer ballance from user to user
    Transfer sum from user to user ballance. If trans_desc is empty - method generates a md5 hash for transaction

    Method:     POST
    URI:        http://domain/ballance_transfer
    Exemple:    -
    Params:     ['uid' - integer, 'ballance' - integer or decimal(19.2), 'uid2' - integer, 'trans_desc' - string]
    Exemple:    ['uid' = 12, 'ballance' = 1.40, 'uid2' = 3, 'trans_desc' = 'f5ceded9f1a974ba98bb8e90fa9d5c22']
    Response:
                [
                  {
                    "user_id": 3,
                    "ballance": "10476.40"
                  },
                  {
                    "user_id": 12,
                    "ballance": "18.60"
                  }
                ]
    Exceptions: Code => description
                503 => 'Failed to add ballance to user'
                504 => 'Failed to sub ballance to user'
                506 => 'Transfer from user to user failed'
                507 => 'User ID format is not correct'
                508 => 'Ballance format is not correct (use decimal 19.2). (1.04 for example)'
                509 => 'User does not exist'
                512 => 'Failed saving ballance history'
    Log:
                id  timastamp           uid sum     operation           transaction_description             user_to
                81	2016-03-21 23:23:54	12	1.40	ballance_transfer	f5ceded9f1a974ba98bb8e90fa9d5c22	3

Get ballance history for all users

    Method:     GET,POST
    URI:        http://domain/ballance_history
    Exemple:    http://domain/ballance_history
    Params:     []
    Response:
                [
                  {
                    "id": 67,
                    "date": "2016-03-21 22:50:09",
                    "user_id": 12,
                    "sum": "0.00",
                    "operation": "add_user",
                    "transaction_description": "6e3df21536d27d680dc5269ed31e4e35",
                    "user_id_to": 0
                  },
                  {
                    "id": 68,
                    "date": "2016-03-21 22:50:47",
                    "user_id": 12,
                    "sum": "1.00",
                    "operation": "add",
                    "transaction_description": "860a651e17ef5ff41098420ba9e5a5bd",
                    "user_id_to": 0
                  },
                  {
                    "id": 69,
                    "date": "2016-03-21 22:51:22",
                    "user_id": 12,
                    "sum": "1.00",
                    "operation": "sub",
                    "transaction_description": "6143d9f56d71c4967709ecfd98ec884e",
                    "user_id_to": 0
                  },
                  {
                    "id": 70,
                    "date": "2016-03-21 22:51:28",
                    "user_id": 12,
                    "sum": "0.00",
                    "operation": "delete_user",
                    "transaction_description": "75b56c39200ec999cc4bb2e5aeb76757",
                    "user_id_to": 0
                  },
                  {
                    "id": 85,
                    "date": "2016-03-22 00:08:38",
                    "user_id": 12,
                    "sum": "1.40",
                    "operation": "ballance_sub",
                    "transaction_description": "trans_desc_34egfdr4423z",
                    "user_id_to": 0
                  },
                  {
                    "id": 86,
                    "date": "2016-03-22 00:08:38",
                    "user_id": 3,
                    "sum": "1.40",
                    "operation": "ballance_add",
                    "transaction_description": "trans_desc_34egfdr4423z",
                    "user_id_to": 0
                  },
                  {
                    "id": 87,
                    "date": "2016-03-22 00:08:38",
                    "user_id": 12,
                    "sum": "1.40",
                    "operation": "ballance_transfer",
                    "transaction_description": "trans_desc_34egfdr4423z",
                    "user_id_to": 3
                  },
                  {
                    "id": 88,
                    "date": "2016-03-22 00:09:15",
                    "user_id": 12,
                    "sum": "32.40",
                    "operation": "ballance_add",
                    "transaction_description": "rfdghjkyuer34567",
                    "user_id_to": 0
                  }
                ]

Get ballance history for user

    Method:     GET,POST
    URI:        http://domain/ballance_history/user_id
    Exemple:    http://domain/ballance_history/3
    Params:     []
    Response:
                [
                  {
                    "id": 86,
                    "date": "2016-03-22 00:08:38",
                    "user_id": 3,
                    "sum": "1.40",
                    "operation": "ballance_add",
                    "transaction_description": "trans_desc_34egfdr4423z",
                    "user_id_to": 0
                  }
                ]