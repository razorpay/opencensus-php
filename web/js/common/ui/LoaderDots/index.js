export default ({ customClass }) => {
  return (
    <span className={`LoaderDots ${customClass}`} data-testid="loader-dots">
      <span>.</span>
      <span>.</span>
      <span>.</span>
    </span>
  );
};
