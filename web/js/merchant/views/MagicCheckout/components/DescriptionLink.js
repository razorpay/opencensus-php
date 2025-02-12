const DescriptionLink = ({ children, href = '/', onClick = () => {} }) => {
  return (
    <a href={href} onClick={onClick} className="magic-checkout-description-link">
      <u>{children}</u>
    </a>
  );
};

export default DescriptionLink;
