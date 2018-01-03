<div>
    <input
      name='name'
      required
      placeholder='Name as in bank account'
      value={{ $data['request']['content']['name'] ?? "" }}>
    <input
      name='bank_account'
      type='number'
      required
      placeholder='Bank Account No.'
      value={{ $data['request']['content']['bank_account'] ?? "" }}>

    <input
      name='bank_code'
      required
      placeholder='IFSC Code/Branch Name'
      value={{ $data['request']['content']['bank_code'] ?? "" }}>
</div>
