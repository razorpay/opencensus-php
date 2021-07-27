const Spinner = ({ center }) => (
  <div class={`spinner ${center ? 'center' : ''}`}>
    <div class="double-bounce1" />
    <div class="double-bounce2" />
  </div>
);

export default Spinner;
