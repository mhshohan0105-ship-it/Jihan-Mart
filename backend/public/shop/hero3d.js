// 3D hero scene: a big cooking pot with steam, floating pan, pot, spatula, tomatoes and particles (Three.js)
(function () {
  const canvas = document.getElementById("hero3d");
  if (!canvas || typeof THREE === "undefined") return;

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
  } catch (e) {
    return; // no WebGL: the CSS glows still look fine
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
  camera.position.set(0, 0, 14);

  // Lights
  scene.add(new THREE.AmbientLight(0xffffff, 0.45));
  const key = new THREE.PointLight(0xf47c20, 2.4, 50);
  key.position.set(6, 6, 8);
  scene.add(key);
  const fill = new THREE.PointLight(0x3b6bd6, 2.2, 50);
  fill.position.set(-8, -4, 6);
  scene.add(fill);
  const rim = new THREE.DirectionalLight(0xffc65c, 0.9);
  rim.position.set(0, 10, -5);
  scene.add(rim);

  const world = new THREE.Group();
  scene.add(world);

  // Materials
  const gold = new THREE.MeshStandardMaterial({ color: 0xffc65c, metalness: 1, roughness: 0.22 });
  const orangeGlossy = new THREE.MeshStandardMaterial({ color: 0xf47c20, metalness: 0.3, roughness: 0.25 });
  const navyGlossy = new THREE.MeshStandardMaterial({ color: 0x2c4a8f, metalness: 0.4, roughness: 0.3 });

  // Centre piece: a big orange cooking pot with a navy lid, gold handles and rising steam
  const centerpiece = new THREE.Group();
  const potBody = new THREE.Mesh(new THREE.CylinderGeometry(1.55, 1.4, 1.9, 64), orangeGlossy);
  centerpiece.add(potBody);
  const potRim = new THREE.Mesh(new THREE.TorusGeometry(1.55, 0.09, 16, 64), gold);
  potRim.rotation.x = Math.PI / 2;
  potRim.position.y = 0.95;
  centerpiece.add(potRim);
  const potLid = new THREE.Mesh(new THREE.SphereGeometry(1.62, 64, 24, 0, Math.PI * 2, 0, Math.PI / 5), navyGlossy);
  potLid.position.y = -0.3;
  centerpiece.add(potLid);
  const potKnob = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.3, 0.3, 32), gold);
  potKnob.position.y = 1.47;
  centerpiece.add(potKnob);
  [-1, 1].forEach((side) => {
    const handle = new THREE.Mesh(new THREE.TorusGeometry(0.34, 0.09, 16, 32, Math.PI), gold);
    handle.position.set(side * 1.62, 0.45, 0);
    handle.rotation.set(Math.PI / 2, 0, side > 0 ? -Math.PI / 2 : Math.PI / 2);
    centerpiece.add(handle);
  });
  // steam puffs above the lid (animated in the loop)
  const steamMat = new THREE.MeshStandardMaterial({ color: 0xffffff, transparent: true, opacity: 0.35, roughness: 1 });
  const steam = [0, 1, 2].map((i) => {
    const puff = new THREE.Mesh(new THREE.SphereGeometry(0.28 + i * 0.06, 20, 16), steamMat.clone());
    puff.position.set((i - 1) * 0.35, 1.9 + i * 0.45, 0);
    puff.userData.offset = i / 3;
    centerpiece.add(puff);
    return puff;
  });
  centerpiece.rotation.set(0.35, -0.6, 0.1);
  world.add(centerpiece);

  // Floating objects
  const floaters = [];
  function addFloater(mesh, x, y, z, speed) {
    mesh.position.set(x, y, z);
    mesh.userData = { baseY: y, speed, phase: Math.random() * Math.PI * 2, spin: (Math.random() - 0.5) * 0.02 };
    world.add(mesh);
    floaters.push(mesh);
  }

  // Kitchen things floating around the box
  const steel = new THREE.MeshStandardMaterial({ color: 0xdfe3ea, metalness: 1, roughness: 0.28 });
  const nonstick = new THREE.MeshStandardMaterial({ color: 0x23262d, metalness: 0.5, roughness: 0.45 });
  const wood = new THREE.MeshStandardMaterial({ color: 0xb8773a, metalness: 0.05, roughness: 0.6 });
  const tomatoRed = new THREE.MeshStandardMaterial({ color: 0xe53935, metalness: 0.1, roughness: 0.25 });
  const leaf = new THREE.MeshStandardMaterial({ color: 0x3f8f3a, metalness: 0.1, roughness: 0.5 });

  // Frying pan: non stick body, steel rim, long handle
  function makePan() {
    const g = new THREE.Group();
    g.add(new THREE.Mesh(new THREE.CylinderGeometry(0.95, 0.8, 0.28, 48), nonstick));
    const rim = new THREE.Mesh(new THREE.TorusGeometry(0.95, 0.05, 12, 48), steel);
    rim.rotation.x = Math.PI / 2;
    rim.position.y = 0.14;
    g.add(rim);
    const handle = new THREE.Mesh(new THREE.BoxGeometry(1.5, 0.12, 0.2), wood);
    handle.position.set(1.65, 0.12, 0);
    g.add(handle);
    return g;
  }
  const pan = makePan();
  pan.rotation.set(0.9, 0.4, -0.3);
  addFloater(pan, -3.4, 2.3, 1, 0.9);

  // Cooking pot with lid, knob and side handles
  function makePot() {
    const g = new THREE.Group();
    g.add(new THREE.Mesh(new THREE.CylinderGeometry(0.8, 0.75, 0.95, 48), steel));
    const lidTop = new THREE.Mesh(new THREE.CylinderGeometry(0.84, 0.84, 0.08, 48), steel);
    lidTop.position.y = 0.52;
    g.add(lidTop);
    const knob = new THREE.Mesh(new THREE.SphereGeometry(0.16, 24, 16), orangeGlossy);
    knob.position.y = 0.66;
    g.add(knob);
    [-1, 1].forEach((side) => {
      const ear = new THREE.Mesh(new THREE.TorusGeometry(0.18, 0.05, 12, 24), navyGlossy);
      ear.position.set(side * 0.92, 0.2, 0);
      ear.rotation.y = Math.PI / 2;
      g.add(ear);
    });
    return g;
  }
  const pot = makePot();
  pot.rotation.set(0.35, 0.2, 0.15);
  addFloater(pot, 3.4, -2.2, 2, 1.1);

  // Wooden spatula
  const spatula = new THREE.Group();
  spatula.add(new THREE.Mesh(new THREE.CylinderGeometry(0.07, 0.09, 2, 16), wood));
  const blade = new THREE.Mesh(new THREE.BoxGeometry(0.5, 0.65, 0.06), wood);
  blade.position.y = 1.25;
  spatula.add(blade);
  spatula.rotation.z = -0.55;
  addFloater(spatula, 1.2, 3.5, -0.5, 0.8);

  // Tomatoes and a green chilli
  function makeTomato(r) {
    const g = new THREE.Group();
    g.add(new THREE.Mesh(new THREE.SphereGeometry(r, 32, 24), tomatoRed));
    const stem = new THREE.Mesh(new THREE.ConeGeometry(r * 0.45, r * 0.3, 5), leaf);
    stem.position.y = r * 0.95;
    g.add(stem);
    return g;
  }
  addFloater(makeTomato(0.42), 3.6, 2.8, -1, 1.3);
  addFloater(makeTomato(0.3), -3.6, -2.4, 0.5, 1.0);
  const chilli = new THREE.Mesh(new THREE.ConeGeometry(0.13, 1.1, 16), leaf);
  chilli.rotation.z = 1.2;
  addFloater(chilli, -1.2, -3.4, 2.5, 1.5);

  // Steel bowls
  const bowl = new THREE.Mesh(new THREE.SphereGeometry(0.34, 32, 16, 0, Math.PI * 2, Math.PI / 2, Math.PI / 2), steel);
  addFloater(bowl, 0.4, -3.2, 1.5, 1.7);
  addFloater(new THREE.Mesh(new THREE.SphereGeometry(0.2, 32, 32), steel), -2.4, 0.6, 3, 1.2);

  // Particles
  const pCount = 400;
  const pPos = new Float32Array(pCount * 3);
  for (let i = 0; i < pCount; i++) {
    pPos[i * 3] = (Math.random() - 0.5) * 30;
    pPos[i * 3 + 1] = (Math.random() - 0.5) * 20;
    pPos[i * 3 + 2] = (Math.random() - 0.5) * 16 - 3;
  }
  const pGeo = new THREE.BufferGeometry();
  pGeo.setAttribute("position", new THREE.BufferAttribute(pPos, 3));
  const particles = new THREE.Points(
    pGeo,
    new THREE.PointsMaterial({ color: 0xffc65c, size: 0.06, transparent: true, opacity: 0.8 })
  );
  scene.add(particles);

  // Layout: objects on the right on desktop, centred and dimmer on mobile
  function resize() {
    const w = canvas.clientWidth, h = canvas.clientHeight;
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    const mobile = w < 900;
    world.position.x = mobile ? 0 : 4.6;
    world.position.y = mobile ? -1.5 : 0;
    world.scale.setScalar(mobile ? 0.7 : 1);
    canvas.style.opacity = mobile ? 0.45 : 1;
  }
  window.addEventListener("resize", resize);
  resize();

  // Mouse + scroll parallax
  const mouse = { x: 0, y: 0, tx: 0, ty: 0 };
  window.addEventListener("pointermove", (e) => {
    mouse.tx = (e.clientX / window.innerWidth - 0.5) * 2;
    mouse.ty = (e.clientY / window.innerHeight - 0.5) * 2;
  });

  let visible = true;
  new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(canvas);

  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const clock = new THREE.Clock();

  function tick() {
    requestAnimationFrame(tick);
    if (!visible) return;
    const t = reduced ? 0 : clock.getElapsedTime();

    mouse.x += (mouse.tx - mouse.x) * 0.05;
    mouse.y += (mouse.ty - mouse.y) * 0.05;

    centerpiece.rotation.y = -0.6 + t * 0.35;
    steam.forEach((puff) => {
      const k = (t * 0.35 + puff.userData.offset) % 1; // rise, grow and fade, then start again
      puff.position.y = 1.7 + k * 1.6;
      puff.scale.setScalar(0.7 + k * 0.9);
      puff.material.opacity = 0.4 * (1 - k);
    });
    centerpiece.position.y = Math.sin(t * 1.2) * 0.25;
    const peek = Math.max(0, Math.sin(t * 0.8)) * 0.18; // the lid lifts a little as if the pot is boiling
    potLid.position.y = -0.3 + peek;
    potKnob.position.y = 1.47 + peek;

    floaters.forEach((m) => {
      const d = m.userData;
      m.position.y = d.baseY + Math.sin(t * d.speed + d.phase) * 0.35;
      m.rotation.x += d.spin + 0.004;
      m.rotation.y += 0.01;
    });

    particles.rotation.y = t * 0.02;
    world.rotation.y = mouse.x * 0.35;
    world.rotation.x = mouse.y * 0.2;

    const scroll = Math.min(window.scrollY / window.innerHeight, 1);
    camera.position.z = 14 + scroll * 6;
    camera.position.y = -scroll * 2;

    renderer.render(scene, camera);
  }
  tick();
})();
