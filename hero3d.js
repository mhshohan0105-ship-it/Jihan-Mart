// 3D hero scene: floating gift box, gold rings, gems and particles (Three.js)
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
  const gem = new THREE.MeshStandardMaterial({
    color: 0xffb347, metalness: 0.1, roughness: 0.05, transparent: true, opacity: 0.85, flatShading: true,
  });
  const gemBlue = gem.clone();
  gemBlue.color = new THREE.Color(0x7dd3fc);

  // Gift box (mystery box)
  const gift = new THREE.Group();
  const box = new THREE.Mesh(new THREE.BoxGeometry(2.4, 2.4, 2.4), orangeGlossy);
  gift.add(box);
  const lid = new THREE.Mesh(new THREE.BoxGeometry(2.7, 0.55, 2.7), navyGlossy);
  lid.position.y = 1.35;
  gift.add(lid);
  const ribbonV = new THREE.Mesh(new THREE.BoxGeometry(0.4, 2.95, 2.75), gold);
  ribbonV.position.y = 0.27;
  gift.add(ribbonV);
  const ribbonH = new THREE.Mesh(new THREE.BoxGeometry(2.75, 2.95, 0.4), gold);
  ribbonH.position.y = 0.27;
  gift.add(ribbonH);
  const bowGeo = new THREE.TorusGeometry(0.45, 0.14, 16, 40);
  const bow1 = new THREE.Mesh(bowGeo, gold);
  bow1.position.set(-0.45, 1.85, 0);
  bow1.rotation.set(0, Math.PI / 2, Math.PI / 4);
  const bow2 = bow1.clone();
  bow2.position.x = 0.45;
  bow2.rotation.z = -Math.PI / 4;
  gift.add(bow1, bow2);
  gift.rotation.set(0.35, -0.6, 0.1);
  world.add(gift);

  // Floating objects
  const floaters = [];
  function addFloater(mesh, x, y, z, speed) {
    mesh.position.set(x, y, z);
    mesh.userData = { baseY: y, speed, phase: Math.random() * Math.PI * 2, spin: (Math.random() - 0.5) * 0.02 };
    world.add(mesh);
    floaters.push(mesh);
  }

  // Rings (finger rings with a gem on top)
  function makeRing(gemMat) {
    const g = new THREE.Group();
    g.add(new THREE.Mesh(new THREE.TorusGeometry(0.7, 0.12, 24, 64), gold));
    const stone = new THREE.Mesh(new THREE.OctahedronGeometry(0.32, 0), gemMat);
    stone.position.y = 0.85;
    g.add(stone);
    return g;
  }
  addFloater(makeRing(gem), -3.2, 2.4, 1, 0.9);
  addFloater(makeRing(gemBlue), 3.4, -2.2, 2, 1.1);

  // Loose gems
  addFloater(new THREE.Mesh(new THREE.OctahedronGeometry(0.6, 0), gem), 3.6, 2.8, -1, 1.3);
  addFloater(new THREE.Mesh(new THREE.IcosahedronGeometry(0.45, 0), gemBlue), -3.6, -2.4, 0.5, 1.0);
  addFloater(new THREE.Mesh(new THREE.OctahedronGeometry(0.35, 0), gem), -1.2, -3.4, 2.5, 1.5);

  // Lipstick
  const lipstick = new THREE.Group();
  const tube = new THREE.Mesh(new THREE.CylinderGeometry(0.32, 0.32, 1.2, 32), gold);
  const bullet = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.26, 0.7, 32), orangeGlossy);
  bullet.position.y = 0.95;
  const tip = new THREE.Mesh(new THREE.SphereGeometry(0.22, 24, 16, 0, Math.PI * 2, 0, Math.PI / 2), orangeGlossy);
  tip.position.y = 1.3;
  lipstick.add(tube, bullet, tip);
  lipstick.rotation.z = -0.5;
  addFloater(lipstick, 1.2, 3.6, -0.5, 0.8);

  // Pearl spheres
  const pearl = new THREE.MeshStandardMaterial({ color: 0xfff4f8, metalness: 0.2, roughness: 0.15 });
  addFloater(new THREE.Mesh(new THREE.SphereGeometry(0.28, 32, 32), pearl), 0.4, -3.2, 1.5, 1.7);
  addFloater(new THREE.Mesh(new THREE.SphereGeometry(0.2, 32, 32), pearl), -2.4, 0.6, 3, 1.2);

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

    gift.rotation.y = -0.6 + t * 0.35;
    gift.position.y = Math.sin(t * 1.2) * 0.25;
    lid.position.y = 1.35 + Math.max(0, Math.sin(t * 0.8)) * 0.25; // lid "peeks" open

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
