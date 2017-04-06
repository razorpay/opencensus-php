import './CustButton.styl'

export default (props) => (
  <div class="custom-btn">
    <label for="config-theme-logo-sel">
      <span class="glyphicon glyphicon-folder-open"></span>
      Choose File
    </label>
    <input class="input-btn"
           accept="image/*"
           type="file"
           id="config-theme-logo-sel"
           name="logo"
           onChange={props.handleClick}/>
  </div>
);
