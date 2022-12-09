export default ({ customClass }) => {
  return (
    <span class={`LoaderDots ${customClass}`} data-testid="loader-dots">
      <span>.</span>
      <span>.</span>
      <span>.</span>
    </span>
  );
};
