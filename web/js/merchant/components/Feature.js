const Feature = ({ icon, title, desc }) => (
  <div class="Feature">
    <img class="Feature-icon" src={icon} />

    <div>
      <div class="Feature-title">{title}</div>

      <p class="Feature-desc">{desc}</p>
    </div>
  </div>
);

export default Feature;
