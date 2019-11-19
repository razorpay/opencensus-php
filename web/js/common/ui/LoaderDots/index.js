export default ({ customClass }) => {
  return (
    <span class={`LoaderDots ${customClass}`}>
      <span>.</span>
      <span>.</span>
      <span>.</span>
    </span>
  );
};
