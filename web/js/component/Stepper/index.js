/*
  List = [
    {
      status: 'completed',
      type: 'Dot',
      label: '17th April sent'
    }
  ]
*/
export default ({ list }) => (
  <div class="Stepper">
    {list.map((option, idx) => (
      <div key={idx} class={`Stepper-item ${option.status}`}>
        <span class="item-step">{option.type}</span>

        <span class="item-label">{option.label}</span>
      </div>
    ))}
  </div>
);
