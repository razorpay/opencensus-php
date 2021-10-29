const DescriptionLink = ({ children, href = '/', onClick = () => {} }) => {
  return (
    <a href={href} onClick={onClick} class="super-checkout-description-link">
      <u>{children}</u>
    </a>
  );
};

export default DescriptionLink;
