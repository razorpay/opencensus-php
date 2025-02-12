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
  <div className="Stepper">
    {list.map((option, idx) => (
      <div key={idx} className={`Stepper-item ${option.status}`}>
        <span className="item-step">{option.type}</span>

        <span className="item-label">{option.label}</span>
      </div>
    ))}
  </div>
);
