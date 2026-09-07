(() => {
  const root = document.documentElement;
  root.classList.add("js");

  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) root.setAttribute("data-reduce", "1");

  const header = document.querySelector(".header");
  if (header) {
    const onScroll = () => header.classList.toggle("scrolled", window.scrollY > 12);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
  }

  const nodes = document.querySelectorAll(".reveal");
  const show = (el) => el.classList.add("in");

  if (nodes.length) {
    if (reduce || !("IntersectionObserver" in window)) {
      nodes.forEach(show);
    } else {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              show(entry.target);
              io.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.01, rootMargin: "0px 0px -40px 0px" }
      );
      nodes.forEach((el) => {
        const rect = el.getBoundingClientRect();
        const vh = window.innerHeight || document.documentElement.clientHeight;
        if (rect.top < vh * 0.95 && rect.bottom > 0) show(el);
        else io.observe(el);
      });
      // Failsafe: never leave content invisible
      window.setTimeout(() => nodes.forEach(show), 2500);
    }
  }

  const nav = document.querySelector(".nav");
  const btn = document.querySelector(".menu-btn");
  if (nav && btn) {
    btn.addEventListener("click", () => {
      const open = nav.classList.toggle("open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
    nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        nav.classList.remove("open");
        btn.setAttribute("aria-expanded", "false");
      });
    });
  }

  const tabs = document.querySelectorAll("[data-compare-tab]");
  const panels = document.querySelectorAll("[data-compare-panel]");
  if (tabs.length && panels.length) {
    tabs.forEach((tab) => {
      tab.addEventListener("click", () => {
        const id = tab.getAttribute("data-compare-tab");
        tabs.forEach((t) => t.classList.toggle("on", t === tab));
        panels.forEach((p) => {
          p.classList.toggle("show", p.getAttribute("data-compare-panel") === id);
        });
      });
    });
  }
})();
