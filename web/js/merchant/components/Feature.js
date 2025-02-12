const Feature = ({ icon, title, desc }) => (
  <div className="Feature">
    <img className="Feature-icon" src={icon} />

    <div>
      <div className="Feature-title">{title}</div>

      <p className="Feature-desc">{desc}</p>
    </div>
  </div>
);

export default Feature;
