import { useMemo, useState } from "react";
import {
  Badge,
  Button,
  Card,
  Col,
  Container,
  Form,
  Modal,
  Nav,
  Navbar,
  Row,
} from "react-bootstrap";
import {
  Building2,
  CheckCircle2,
  ChevronRight,
  Clock3,
  Factory,
  Layers3,
  Mail,
  MapPin,
  MessageCircle,
  PackageCheck,
  Palette,
  PanelTop,
  Phone,
  Ruler,
  Search,
  ShieldCheck,
  Sofa,
  Sparkles,
  SquareStack,
  Wrench,
} from "lucide-react";

const apiBase = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";
const crmLoginUrl = import.meta.env.VITE_CRM_LOGIN_URL || "http://127.0.0.1:5173/login";

const logo = new URL("../assets/logo.png", import.meta.url).href;
const heroPhoto = new URL("../assets/hero-workshop-photo.png", import.meta.url).href;
const melamina = new URL("../assets/melamina.svg", import.meta.url).href;
const canto = new URL("../assets/canto.svg", import.meta.url).href;

const brands = [
  {
    name: "Pelíkano",
    tone: "Warm oak",
    description: "Diseños y texturas para proyectos contemporáneos con presencia visual.",
  },
  {
    name: "Vesto",
    tone: "Graphite board",
    description: "Tableros para mobiliario, revestimiento y proyectos funcionales.",
  },
  {
    name: "Tableros Hispanos",
    tone: "Natural fiber",
    description: "Tableros melaminados y aglomerados para diferentes aplicaciones.",
  },
];

const categories = [
  ["Melamina blanca", PanelTop, "Superficies limpias para cocina, closet y mobiliario comercial."],
  ["Melamina de colores", Palette, "Tonos sobrios y acentos para proyectos personalizados."],
  ["Diseños tipo madera", Layers3, "Acabados naturales para ambientes cálidos y modernos."],
  ["Texturas y acabados", Sparkles, "Relieves, mates y superficies de uso cotidiano."],
  ["Tableros para muebles", Sofa, "Planchas pensadas para fabricar módulos y estructuras."],
  ["Tableros para revestimiento", SquareStack, "Soluciones para muros, paneles y frentes decorativos."],
  ["Servicios de corte", Ruler, "Cortes coordinados segun medidas del proyecto."],
  ["Canteado", PackageCheck, "Acabado de bordes para piezas listas para armar."],
  ["CNC", Factory, "Mecanizado para detalles, ranuras y formas especiales."],
  ["Proyectos personalizados", Building2, "Acompañamiento para muebles a medida."],
  ["Accesorios y herrajes", Wrench, "Complementos funcionales sin marca propia inventada."],
];

const catalog = [
  {
    brand: "CESCORT",
    category: "Melamina blanca",
    color: "Blanco",
    finish: "Mate",
    thickness: "18 mm",
    price: 200,
    name: "Melamina Blanca 18 mm",
    image: melamina,
  },
  {
    brand: "Vesto",
    category: "Melamina blanca",
    color: "Blanco",
    finish: "RH",
    thickness: "18 mm",
    price: "Consultar",
    name: "Melamina Vesto RH 18 mm",
    image: melamina,
  },
  {
    brand: "Pelíkano",
    category: "Melamina blanca",
    color: "Blanco",
    finish: "Mate",
    thickness: "18 mm",
    price: 300,
    name: "Melamina Pelíkano Blanca 18 mm",
    image: melamina,
  },
  {
    brand: "Tableros Hispanos",
    category: "Diseños tipo madera",
    color: "Roble",
    finish: "Madera",
    thickness: "18 mm",
    price: 280,
    name: "Melamina Hispano Roble",
    image: melamina,
  },
  {
    brand: "CESCORT",
    category: "Diseños tipo madera",
    color: "Nogal",
    finish: "Madera",
    thickness: "18 mm",
    price: 260,
    name: "Melamina color nogal",
    image: melamina,
  },
  {
    brand: "Vesto",
    category: "Texturas y acabados",
    color: "Cemento",
    finish: "Texturado",
    thickness: "18 mm",
    price: "Consultar",
    name: "Melamina textura cemento",
    image: melamina,
  },
  {
    brand: "CESCORT",
    category: "Canteado",
    color: "Varios",
    finish: "Delgado",
    thickness: "0.45 mm",
    price: 1.5,
    name: "Canto delgado",
    image: canto,
  },
  {
    brand: "CESCORT",
    category: "Canteado",
    color: "Varios",
    finish: "Grueso",
    thickness: "2 mm",
    price: 5,
    name: "Canto grueso",
    image: canto,
  },
  {
    brand: "CESCORT",
    category: "Servicios de corte",
    color: "N/A",
    finish: "Servicio",
    thickness: "A medida",
    price: "Consultar",
    name: "Servicio de corte",
    image: heroPhoto,
  },
  {
    brand: "CESCORT",
    category: "Canteado",
    color: "N/A",
    finish: "Servicio",
    thickness: "A medida",
    price: "Consultar",
    name: "Servicio de canteado",
    image: canto,
  },
  {
    brand: "CESCORT",
    category: "CNC",
    color: "N/A",
    finish: "Servicio",
    thickness: "A medida",
    price: "Consultar",
    name: "Servicio CNC",
    image: heroPhoto,
  },
];

const services = [
  "Venta de planchas de melamina",
  "Corte con escuadradora",
  "Canteado",
  "Corte CNC",
  "Diseno de muebles",
  "Fabricación de cocinas",
  "Fabricación de closets",
  "Centros de TV",
  "Roperos",
  "Walk-in closet",
  "Wall panel",
  "Muebles comerciales",
  "Asesoria para eleccion de materiales",
];

const projects = [
  ["Cocina integral", "Melamina blanca y madera", "kitchen"],
  ["Centro de entretenimiento", "Tablero texturado", "media"],
  ["Ropero empotrado", "Melamina nogal", "wardrobe"],
  ["Closet", "Blanco mate", "closet"],
  ["Mueble bar", "Madera oscura", "bar"],
  ["Mueble de oficina", "Grafito y roble", "office"],
  ["Wall panel", "Revestimiento ranurado", "wall"],
  ["Proyecto comercial", "Modulo a medida", "store"],
];

const trustItems = [
  ["Atencion personalizada", MessageCircle],
  ["Materiales seleccionados", ShieldCheck],
  ["Corte preciso", Ruler],
  ["Entrega coordinada", CheckCircle2],
];

const contact = {
  phone: import.meta.env.VITE_CESCORT_PHONE || "[Completar teléfono]",
  whatsapp: import.meta.env.VITE_CESCORT_WHATSAPP || "[Completar WhatsApp]",
  email: import.meta.env.VITE_CESCORT_EMAIL || "[Completar correo]",
  address: import.meta.env.VITE_CESCORT_ADDRESS || "[Completar dirección de CESCORT]",
  schedule: import.meta.env.VITE_CESCORT_SCHEDULE || "Horario de atención configurable",
  facebook: import.meta.env.VITE_CESCORT_FACEBOOK || "Facebook configurable",
  instagram: import.meta.env.VITE_CESCORT_INSTAGRAM || "Instagram configurable",
};

const emptyQuote = {
  nombre: "",
  dni_ruc: "",
  email: "",
  telefono: "",
  tipo_proyecto: "",
  marca_interes: "",
  producto_servicio: "",
  medidas: "",
  mensaje: "",
};

const emptyLogin = {
  usuario: "",
  password: "",
};

function formatPrice(value) {
  return typeof value === "number" ? `S/ ${value.toFixed(2)}` : value;
}

function uniqueValues(key) {
  return [...new Set(catalog.map((item) => item[key]))].filter(Boolean).sort();
}

async function apiRequest(path, body) {
  const response = await fetch(`${apiBase}${path}`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify(body),
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw payload;
  }

  return payload;
}

export default function App() {
  const [filters, setFilters] = useState({
    search: "",
    brand: "",
    category: "",
    color: "",
    finish: "",
    thickness: "",
    price: "",
  });
  const [quote, setQuote] = useState(emptyQuote);
  const [quoteStatus, setQuoteStatus] = useState("");
  const [login, setLogin] = useState(emptyLogin);
  const [loginStatus, setLoginStatus] = useState("");
  const [loadingLogin, setLoadingLogin] = useState(false);
  const [loadingQuote, setLoadingQuote] = useState(false);
  const [showLogin, setShowLogin] = useState(false);
  const [selectedProject, setSelectedProject] = useState(null);

  const filteredCatalog = useMemo(() => {
    const query = filters.search.trim().toLowerCase();

    return catalog.filter((item) => {
      const price = typeof item.price === "number" ? item.price : null;
      const matchesSearch = !query || [item.name, item.brand, item.category, item.color, item.finish, item.thickness]
        .some((value) => String(value).toLowerCase().includes(query));

      const matchesPrice = !filters.price
        || filters.price === "consultar" && price === null
        || filters.price === "low" && price !== null && price <= 50
        || filters.price === "mid" && price !== null && price > 50 && price <= 250
        || filters.price === "high" && price !== null && price > 250;

      return matchesSearch
        && (!filters.brand || item.brand === filters.brand)
        && (!filters.category || item.category === filters.category)
        && (!filters.color || item.color === filters.color)
        && (!filters.finish || item.finish === filters.finish)
        && (!filters.thickness || item.thickness === filters.thickness)
        && matchesPrice;
    });
  }, [filters]);

  const openQuote = (item = {}) => {
    setQuote((current) => ({
      ...current,
      marca_interes: item.brand || current.marca_interes,
      producto_servicio: item.name || item.title || current.producto_servicio,
      tipo_proyecto: item.category || current.tipo_proyecto,
    }));
    setQuoteStatus("");
    document.querySelector("#cotizacion")?.scrollIntoView({ behavior: "smooth" });
  };

  const submitQuote = async (event) => {
    event.preventDefault();

    if (!quote.nombre.trim() || !quote.dni_ruc.trim() || !quote.telefono.trim() || !quote.producto_servicio.trim()) {
      setQuoteStatus("Completa nombre, DNI o RUC, teléfono y producto o servicio.");
      return;
    }

    setLoadingQuote(true);
    setQuoteStatus("");

    try {
      await apiRequest("/public/solicitudes", {
        nombre: quote.nombre,
        email: quote.email,
        telefono: quote.telefono,
        dni_ruc: quote.dni_ruc,
        direccion: contact.address,
        producto: quote.producto_servicio,
        cantidad_aproximada: 1,
        mensaje: [
          `Tipo de proyecto: ${quote.tipo_proyecto || "No especificado"}`,
          `Marca de interes: ${quote.marca_interes || "No especificada"}`,
          `Medidas: ${quote.medidas || "No especificadas"}`,
          quote.mensaje,
        ].filter(Boolean).join("\n"),
      });
      setQuote(emptyQuote);
      setQuoteStatus("Solicitud enviada. El equipo comercial la revisara desde el CRM/ERP.");
    } catch {
      setQuoteStatus("La solicitud quedo preparada. Verifica la conexion con el backend para registrarla.");
    } finally {
      setLoadingQuote(false);
    }
  };

  const submitDemoLogin = async (event) => {
    event.preventDefault();
    setLoadingLogin(true);
    setLoginStatus("");

    try {
      const payload = await apiRequest("/public/demo-access", login);
      window.location.assign(payload.redirect_url || crmLoginUrl);
    } catch {
      setLoginStatus("Usuario o contraseña incorrectos");
    } finally {
      setLoadingLogin(false);
    }
  };

  return (
    <>
      <Navbar expand="xl" fixed="top" className="site-nav">
        <Container fluid="xxl">
          <Navbar.Brand href="#inicio" className="brand">
            <img src={logo} alt="Logo CESCORT" />
            <span>CESCORT / VillaMueble</span>
          </Navbar.Brand>
          <Navbar.Toggle aria-controls="landing-nav" />
          <Navbar.Collapse id="landing-nav">
            <Nav className="ms-auto align-items-xl-center gap-xl-1">
              <Nav.Link href="#inicio">Inicio</Nav.Link>
              <Nav.Link href="#marcas">Marcas</Nav.Link>
              <Nav.Link href="#productos">Productos</Nav.Link>
              <Nav.Link href="#servicios">Servicios</Nav.Link>
              <Nav.Link href="#proyectos">Proyectos</Nav.Link>
              <Nav.Link href="#nosotros">Nosotros</Nav.Link>
              <Nav.Link href="#contacto">Contacto</Nav.Link>
              <Button variant="outline-dark" size="sm" onClick={() => setShowLogin(true)}>Iniciar sesión</Button>
              <Button variant="dark" size="sm" onClick={() => openQuote()}>Solicitar cotización</Button>
            </Nav>
          </Navbar.Collapse>
        </Container>
      </Navbar>

      <main>
        <section
          id="inicio"
          className="hero"
          style={{ backgroundImage: `linear-gradient(90deg, rgba(32, 32, 30, 0.94), rgba(47, 48, 45, 0.72), rgba(47, 48, 45, 0.18)), url(${heroPhoto})` }}
        >
          <Container fluid="xxl">
            <Row className="align-items-center">
              <Col lg={8} xl={7} className="reveal">
                <Badge bg="light" text="dark" className="hero-badge">Empresa peruana de materiales y fabricación</Badge>
                <h1>Materiales que convierten tus ideas en espacios reales</h1>
                <p className="lead-copy">
                  Melamina, tableros, accesorios y soluciones para proyectos residenciales,
                  comerciales y profesionales.
                </p>
                <div className="hero-actions">
                  <Button href="#productos" variant="light">Ver productos</Button>
                  <Button variant="outline-light" onClick={() => openQuote()}>Solicitar cotización</Button>
                </div>
              </Col>
            </Row>
          </Container>
        </section>

        <section id="marcas" className="section">
          <Container fluid="xxl">
            <div className="section-head reveal">
              <p className="eyebrow">Marcas</p>
              <h2>Tableros y melaminas para diferentes estilos de proyecto.</h2>
              <p>Usamos nombres tipograficos por marca, sin reutilizar logotipos protegidos.</p>
            </div>
            <Row className="g-4">
              {brands.map((brand, index) => (
                <Col lg={4} key={brand.name}>
                  <Card className={`brand-card brand-${index + 1} h-100 reveal`}>
                    <Card.Body>
                      <span className="brand-tone">{brand.tone}</span>
                      <Card.Title>{brand.name}</Card.Title>
                      <Card.Text>{brand.description}</Card.Text>
                      <Button variant="link" onClick={() => {
                        setFilters((current) => ({ ...current, brand: brand.name }));
                        document.querySelector("#productos")?.scrollIntoView({ behavior: "smooth" });
                      }}>
                        Explorar coleccion <ChevronRight size={16} />
                      </Button>
                    </Card.Body>
                  </Card>
                </Col>
              ))}
            </Row>
          </Container>
        </section>

        <section id="productos" className="section muted">
          <Container fluid="xxl">
            <div className="section-head reveal">
              <p className="eyebrow">Categorias</p>
              <h2>Catálogo organizado para elegir con claridad.</h2>
            </div>
            <Row className="g-3 category-grid">
              {categories.map(([title, Icon, description]) => (
                <Col sm={6} lg={4} xl={3} key={title}>
                  <button className="category-card reveal" onClick={() => {
                    setFilters((current) => ({ ...current, category: title }));
                    document.querySelector("#catalogo")?.scrollIntoView({ behavior: "smooth" });
                  }}>
                    <Icon size={24} />
                    <strong>{title}</strong>
                    <span>{description}</span>
                  </button>
                </Col>
              ))}
            </Row>

            <div id="catalogo" className="catalog-panel reveal">
              <div className="catalog-title">
                <div>
                  <p className="eyebrow">Catálogo visual</p>
                  <h2>Productos y servicios referenciales.</h2>
                </div>
                <Button variant="outline-dark" onClick={() => setFilters({ search: "", brand: "", category: "", color: "", finish: "", thickness: "", price: "" })}>
                  Limpiar filtros
                </Button>
              </div>

              <Row className="g-3 filter-row">
                <Col md={6} xl={3}>
                  <div className="search-field">
                    <Search size={18} />
                    <Form.Control
                      value={filters.search}
                      onChange={(event) => setFilters({ ...filters, search: event.target.value })}
                      placeholder="Buscar por nombre, marca o acabado"
                    />
                  </div>
                </Col>
                {[
                  ["brand", "Marca", uniqueValues("brand")],
                  ["category", "Categoria", uniqueValues("category")],
                  ["color", "Color", uniqueValues("color")],
                  ["finish", "Acabado", uniqueValues("finish")],
                  ["thickness", "Espesor", uniqueValues("thickness")],
                ].map(([key, label, options]) => (
                  <Col md={6} xl={key === "thickness" ? 2 : 3} key={key}>
                    <Form.Select value={filters[key]} onChange={(event) => setFilters({ ...filters, [key]: event.target.value })}>
                      <option value="">{label}</option>
                      {options.map((option) => <option key={option} value={option}>{option}</option>)}
                    </Form.Select>
                  </Col>
                ))}
                <Col md={6} xl={3}>
                  <Form.Select value={filters.price} onChange={(event) => setFilters({ ...filters, price: event.target.value })}>
                    <option value="">Rango de precio</option>
                    <option value="low">Hasta S/ 50</option>
                    <option value="mid">S/ 51 a S/ 250</option>
                    <option value="high">Más de S/ 250</option>
                    <option value="consultar">Consultar</option>
                  </Form.Select>
                </Col>
              </Row>

              <Row className="g-4 mt-2">
                {filteredCatalog.map((item) => (
                  <Col md={6} xl={4} key={`${item.brand}-${item.name}`}>
                    <Card className="product-card h-100">
                      <Card.Img variant="top" src={item.image} alt={item.name} />
                      <Card.Body>
                        <div className="product-meta">
                          <Badge bg="secondary">{item.brand}</Badge>
                          <span>{item.category}</span>
                        </div>
                        <Card.Title>{item.name}</Card.Title>
                        <Card.Text>
                          {item.thickness} · {item.finish} · {item.color}
                        </Card.Text>
                        <div className="product-footer">
                          <strong>{formatPrice(item.price)}</strong>
                          <Button variant="dark" size="sm" onClick={() => openQuote(item)}>Solicitar cotización</Button>
                        </div>
                      </Card.Body>
                    </Card>
                  </Col>
                ))}
                {filteredCatalog.length === 0 && (
                  <Col xs={12}>
                    <div className="empty-state">No encontramos productos con esos filtros.</div>
                  </Col>
                )}
              </Row>
            </div>
          </Container>
        </section>

        <section id="servicios" className="service-band">
          <Container fluid="xxl">
            <Row className="g-4 align-items-start">
              <Col lg={5} className="reveal">
                <p className="eyebrow light">Servicios</p>
                <h2>Del tablero al mueble terminado.</h2>
                <p>Servicios pensados para agilizar compras, cortes y fabricación sin perder control de medidas ni acabados.</p>
              </Col>
              <Col lg={7}>
                <Row className="g-3">
                  {services.map((service) => (
                    <Col sm={6} key={service}>
                      <button className="service-item reveal" onClick={() => openQuote({ title: service })}>
                        {service}
                      </button>
                    </Col>
                  ))}
                </Row>
              </Col>
            </Row>
          </Container>
        </section>

        <section id="proyectos" className="section">
          <Container fluid="xxl">
            <div className="section-head reveal">
              <p className="eyebrow">Proyectos</p>
              <h2>Galeria visual editable para trabajos reales.</h2>
            </div>
            <Row className="g-4">
              {projects.map(([name, material, type], index) => (
                <Col md={6} xl={3} key={name}>
                  <Card className="project-card h-100 reveal">
                    <div className={`project-image project-${type}`}>
                      <span>{String(index + 1).padStart(2, "0")}</span>
                    </div>
                    <Card.Body>
                      <Card.Title>{name}</Card.Title>
                      <Card.Text>{material}</Card.Text>
                      <Button variant="outline-dark" size="sm" onClick={() => setSelectedProject({ name, material })}>Ver proyecto</Button>
                    </Card.Body>
                  </Card>
                </Col>
              ))}
            </Row>
          </Container>
        </section>

        <section id="nosotros" className="section split-section">
          <Container fluid="xxl">
            <Row className="g-5 align-items-center">
              <Col lg={5} className="reveal">
                <p className="eyebrow">Nosotros</p>
                <h2>Una atención cercana para proyectos de melamina en Perú.</h2>
              </Col>
              <Col lg={7} className="reveal">
                <p className="section-copy">
                  CESCORT/VillaMueble comercializa tableros, melaminas, cantos,
                  accesorios y servicios de fabricación de muebles. El enfoque es
                  coordinar materiales, medidas y servicios para que cada proyecto
                  avance con claridad desde la cotización hasta la entrega.
                </p>
                <Row className="g-3 mt-2">
                  {trustItems.map(([label, Icon]) => (
                    <Col sm={6} key={label}>
                      <div className="trust-card">
                        <Icon size={24} />
                        <strong>{label}</strong>
                      </div>
                    </Col>
                  ))}
                </Row>
              </Col>
            </Row>
          </Container>
        </section>

        <section id="cotizacion" className="section muted">
          <Container fluid="xxl">
            <Row className="g-5 align-items-start">
              <Col lg={5} className="reveal">
                <p className="eyebrow">Cotización</p>
                <h2>Solicita una respuesta comercial.</h2>
                <p className="section-copy">
                  Este formulario no registra ventas. Crea una solicitud comercial
                  para revisar medidas, materiales y disponibilidad.
                </p>
              </Col>
              <Col lg={7} className="reveal">
                <Form className="quote-form" onSubmit={submitQuote}>
                  <Row className="g-3">
                    <Col md={6}>
                      <Form.Label>Nombre</Form.Label>
                      <Form.Control required value={quote.nombre} onChange={(e) => setQuote({ ...quote, nombre: e.target.value })} />
                    </Col>
                    <Col md={6}>
                      <Form.Label>DNI o RUC</Form.Label>
                      <Form.Control required value={quote.dni_ruc} onChange={(e) => setQuote({ ...quote, dni_ruc: e.target.value })} />
                    </Col>
                    <Col md={6}>
                      <Form.Label>Correo</Form.Label>
                      <Form.Control type="email" value={quote.email} onChange={(e) => setQuote({ ...quote, email: e.target.value })} />
                    </Col>
                    <Col md={6}>
                      <Form.Label>Teléfono</Form.Label>
                      <Form.Control required value={quote.telefono} onChange={(e) => setQuote({ ...quote, telefono: e.target.value })} />
                    </Col>
                    <Col md={6}>
                      <Form.Label>Tipo de proyecto</Form.Label>
                      <Form.Select value={quote.tipo_proyecto} onChange={(e) => setQuote({ ...quote, tipo_proyecto: e.target.value })}>
                        <option value="">Seleccionar</option>
                        {["Cocina", "Closet", "Centro de TV", "Wall panel", "Mueble comercial", "Otro"].map((item) => (
                          <option key={item} value={item}>{item}</option>
                        ))}
                      </Form.Select>
                    </Col>
                    <Col md={6}>
                      <Form.Label>Marca de interes</Form.Label>
                      <Form.Select value={quote.marca_interes} onChange={(e) => setQuote({ ...quote, marca_interes: e.target.value })}>
                        <option value="">Seleccionar</option>
                        {brands.map((brand) => <option key={brand.name} value={brand.name}>{brand.name}</option>)}
                        <option value="CESCORT">CESCORT</option>
                      </Form.Select>
                    </Col>
                    <Col md={6}>
                      <Form.Label>Producto o servicio</Form.Label>
                      <Form.Control required value={quote.producto_servicio} onChange={(e) => setQuote({ ...quote, producto_servicio: e.target.value })} />
                    </Col>
                    <Col md={6}>
                      <Form.Label>Medidas aproximadas</Form.Label>
                      <Form.Control value={quote.medidas} onChange={(e) => setQuote({ ...quote, medidas: e.target.value })} />
                    </Col>
                    <Col xs={12}>
                      <Form.Label>Mensaje adicional</Form.Label>
                      <Form.Control as="textarea" rows={4} value={quote.mensaje} onChange={(e) => setQuote({ ...quote, mensaje: e.target.value })} />
                    </Col>
                  </Row>
                  <Button type="submit" variant="dark" disabled={loadingQuote} className="mt-4 w-100">
                    {loadingQuote ? "Enviando..." : "Enviar solicitud"}
                  </Button>
                  {quoteStatus && <p className="form-status">{quoteStatus}</p>}
                </Form>
              </Col>
            </Row>
          </Container>
        </section>

        <section id="contacto" className="section contact-section">
          <Container fluid="xxl">
            <Row className="g-4 align-items-stretch">
              <Col lg={5} className="reveal">
                <p className="eyebrow">Contacto</p>
                <h2>Canales configurables para atención comercial.</h2>
                <div className="map-block">
                  <MapPin size={28} />
                  <strong>Ubicación en Perú</strong>
                  <span>{contact.address}</span>
                </div>
              </Col>
              <Col lg={7}>
                <Row className="g-3">
                  {[
                    ["Teléfono", contact.phone, Phone],
                    ["WhatsApp", contact.whatsapp, MessageCircle],
                    ["Correo", contact.email, Mail],
                    ["Horario", contact.schedule, Clock3],
                  ].map(([label, value, Icon]) => (
                    <Col md={6} key={label}>
                      <div className="contact-card reveal">
                        <Icon size={22} />
                        <span>{label}</span>
                        <strong>{value}</strong>
                      </div>
                    </Col>
                  ))}
                </Row>
              </Col>
            </Row>
          </Container>
        </section>
      </main>

      <footer className="footer">
        <Container fluid="xxl">
          <Row className="g-4">
            <Col lg={4}>
              <div className="brand footer-brand">
                <img src={logo} alt="Logo CESCORT" />
                <span>CESCORT / VillaMueble</span>
              </div>
              <p>Melamina, tableros, servicios de corte y soluciones para muebles a medida.</p>
            </Col>
            <Col sm={6} lg={2}>
              <strong>Enlaces</strong>
              <a href="#inicio">Inicio</a>
              <a href="#productos">Productos</a>
              <a href="#servicios">Servicios</a>
              <a href="#cotizacion">Cotización</a>
            </Col>
            <Col sm={6} lg={2}>
              <strong>Marcas</strong>
              {brands.map((brand) => <span key={brand.name}>{brand.name}</span>)}
            </Col>
            <Col sm={6} lg={2}>
              <strong>Servicios</strong>
              <span>Corte</span>
              <span>Canteado</span>
              <span>CNC</span>
              <span>Fabricación</span>
            </Col>
            <Col sm={6} lg={2}>
              <strong>Contacto</strong>
              <span>{contact.phone}</span>
              <span>{contact.email}</span>
              <span>{contact.facebook}</span>
              <span>{contact.instagram}</span>
            </Col>
          </Row>
          <div className="footer-bottom">
            <span>Derechos reservados CESCORT S.A.C.</span>
            <a href="#inicio">Politica de privacidad</a>
            <a href="#inicio">Terminos y condiciones</a>
          </div>
        </Container>
      </footer>

      <Modal show={showLogin} onHide={() => setShowLogin(false)} centered>
        <Modal.Header closeButton>
          <Modal.Title>Acceso al sistema de ventas</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <Form className="modal-form" onSubmit={submitDemoLogin}>
            <Form.Label>Usuario</Form.Label>
            <Form.Control
              autoComplete="username"
              required
              value={login.usuario}
              onChange={(event) => setLogin({ ...login, usuario: event.target.value })}
            />
            <Form.Label>Contraseña</Form.Label>
            <Form.Control
              autoComplete="current-password"
              type="password"
              required
              value={login.password}
              onChange={(event) => setLogin({ ...login, password: event.target.value })}
            />
            <Button type="submit" variant="dark" disabled={loadingLogin}>
              {loadingLogin ? "Verificando..." : "Continuar al login"}
            </Button>
            {loginStatus && <p className="modal-status error">{loginStatus}</p>}
          </Form>
        </Modal.Body>
      </Modal>

      <Modal show={!!selectedProject} onHide={() => setSelectedProject(null)} centered>
        <Modal.Header closeButton>
          <Modal.Title>{selectedProject?.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="section-copy">
            Proyecto de referencia para reemplazar por imágenes reales del negocio.
            Material sugerido: {selectedProject?.material}.
          </p>
          <Button variant="dark" onClick={() => {
            openQuote({ title: selectedProject?.name });
            setSelectedProject(null);
          }}>
            Solicitar proyecto similar
          </Button>
        </Modal.Body>
      </Modal>
    </>
  );
}
