<div>
    <input
      name='bank_account[name]'
      required
      placeholder='Name as in bank account'
      @if (isset($data['request']['content']['input']['bank_account[name]']))
        disabled
      @endif
      value="{{ $data['request']['content']['input']['bank_account[name]'] ?? '' }}">
    <input
      name='bank_account[account_number]'
      pattern="^[a-zA-Z0-9]+$"
      required
      placeholder='Bank Account No.'
      @if (isset($data['request']['content']['input']['bank_account[account_number]']))
        disabled
      @endif
      value="{{ $data['request']['content']['input']['bank_account[account_number]'] ?? '' }}">

    <div id="help-container">
      <input
        name='bank_account[ifsc]'
        required
        placeholder='IFSC Code'
        @if (isset($data['request']['content']['input']['bank_account[ifsc]']))
            disabled
        @endif
        value="{{ $data['request']['content']['input']['bank_account[ifsc]'] ?? '' }}">
      <span id="icon">info</span>
      <span id="help"></span>
    </div>

    <select
      name='bank_account[account_type]'
      required
    >
      @if (isset($data['request']['content']['input']['bank_account[account_type]']))
        <option value="{{ $data['request']['content']['input']['bank_account[account_type]'] }}">
          @if ($data['request']['content']['input']['bank_account[account_type]'] == 'savings')
            Savings Account
          @elseif ($data['request']['content']['input']['bank_account[account_type]'] == 'current')
            Current Account
          @endif
        </option>
      @else
        <option value="">Account Type</option>
        <option value="savings">Savings Account</option>
        <option value="current">Current Account</option>
      @endif
    </select>
</div>
