import type { Directive } from "vue";

// Scroll-triggered reveal: fades + slides an element up the first time it
// crosses into the viewport. `v-reveal="120"` delays the transition by 120ms
// (for staggering a group of siblings). Under prefers-reduced-motion the
// element still fades in on scroll (so the effect the user asked for is
// never silently skipped) — it just loses the slide/scale motion, which is
// handled purely in CSS (see resources/css/app.css).

const VISIBLE_CLASS = "reveal-visible";

const observer =
    typeof window !== "undefined" && "IntersectionObserver" in window
        ? new IntersectionObserver(
              (entries) => {
                  for (const entry of entries) {
                      if (entry.isIntersecting) {
                          entry.target.classList.add(VISIBLE_CLASS);
                          observer?.unobserve(entry.target);
                      }
                  }
              },
              { threshold: 0.12, rootMargin: "0px 0px -10% 0px" },
          )
        : null;

export const vReveal: Directive<HTMLElement, number | undefined> = {
    mounted(el, binding) {
        if (!observer) {
            el.classList.add(VISIBLE_CLASS);
            return;
        }

        el.classList.add("reveal");
        if (typeof binding.value === "number") {
            el.style.transitionDelay = `${binding.value}ms`;
        }
        observer.observe(el);
    },
    unmounted(el) {
        observer?.unobserve(el);
    },
};
