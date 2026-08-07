export function scrollbarCompensation(viewportWidth, clientWidth) {
  const viewport = Number.isFinite(viewportWidth) ? viewportWidth : 0;
  const client = Number.isFinite(clientWidth) ? clientWidth : viewport;
  return Math.max(0, viewport - client);
}

export function afterNextPaint(scheduleFrame = requestAnimationFrame) {
  return new Promise((resolve) => {
    scheduleFrame(() => scheduleFrame(resolve));
  });
}

export function transitionTimeout(element, fallbackMs = 360) {
  return new Promise((resolve) => {
    let settled = false;
    let timerId;

    const finish = () => {
      if (settled) return;
      settled = true;
      if (timerId !== undefined) clearTimeout(timerId);
      element?.removeEventListener?.('transitionend', onTransitionEnd);
      resolve();
    };

    const onTransitionEnd = (event) => {
      if (event.target === element) finish();
    };

    element?.addEventListener?.('transitionend', onTransitionEnd);
    timerId = setTimeout(finish, Math.max(0, fallbackMs));
  });
}
