<div>
    <input
      name='bank_account[name]'
      required
      placeholder='Name as in bank account'
      value={{ $data['request']['content']['input']['bank_account']['name'] ?? "" }}>
    <input
      name='bank_account[number]'
      type='number'
      required
      placeholder='Bank Account No.'
      value={{ $data['request']['content']['input']['bank_account']['number'] ?? "" }}>

    <div id="help-container">
      <input
        name='bank_account[ifsc]'
        required
        placeholder='IFSC Code'
        value={{ $data['request']['content']['input']['bank_account']['ifsc'] ?? "" }}>
      <span id="icon">info</span>
      <span id="help"></span>
    </div>
</div>
