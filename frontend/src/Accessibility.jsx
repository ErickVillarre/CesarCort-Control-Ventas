import { useCallback, useEffect, useRef, useState } from "react";
import { Accessibility, Volume2, VolumeX, X } from "lucide-react";

const STORAGE_KEY = "cescort-vision-mode";

const modes = [
  ["normal", "Visualización normal"],
  ["protanopia", "Protanopia"],
  ["deuteranopia", "Deuteranopia"],
  ["tritanopia", "Tritanopia"],
  ["acromatopsia", "Acromatopsia"],
  ["alto-contraste", "Alto contraste"],
];

function storedMode() {
  try {
    const value = localStorage.getItem(STORAGE_KEY);
    return modes.some(([key]) => key === value) ? value : "normal";
  } catch {
    return "normal";
  }
}

export default function AccessibilityPanel() {
  const [mode, setMode] = useState(storedMode);
  const [open, setOpen] = useState(false);
  const [voiceEnabled, setVoiceEnabled] = useState(false);
  const [message, setMessage] = useState("");
  const lastSpoken = useRef({ text: "", at: 0 });
  const speechAvailable = typeof window !== "undefined" && "speechSynthesis" in window && "SpeechSynthesisUtterance" in window;

  useEffect(() => {
    document.documentElement.dataset.vision = mode;
    try {
      localStorage.setItem(STORAGE_KEY, mode);
    } catch {
      // La interfaz sigue funcionando si el navegador bloquea el almacenamiento.
    }
  }, [mode]);

  const speak = useCallback((text, force = false) => {
    if ((!voiceEnabled && !force) || !speechAvailable) return;
    const now = Date.now();
    if (lastSpoken.current.text === text && now - lastSpoken.current.at < 1600) return;
    lastSpoken.current = { text, at: now };
    window.speechSynthesis.cancel();
    const utterance = new window.SpeechSynthesisUtterance(text);
    utterance.lang = "es-PE";
    utterance.rate = 0.92;
    window.speechSynthesis.speak(utterance);
  }, [speechAvailable, voiceEnabled]);

  useEffect(() => {
    const announceAction = (event) => {
      const control = event.target.closest?.("[data-voice]");
      if (!control || control.disabled) return;
      speak(control.dataset.voice);
    };
    const announceResult = (event) => {
      if (event.detail?.kind === "success") speak("Operación completada.");
      if (event.detail?.kind === "error") speak("Ocurrió un error. Revisa el mensaje en pantalla.");
    };
    document.addEventListener("focusin", announceAction);
    document.addEventListener("click", announceAction);
    window.addEventListener("cescort:announce", announceResult);
    return () => {
      document.removeEventListener("focusin", announceAction);
      document.removeEventListener("click", announceAction);
      window.removeEventListener("cescort:announce", announceResult);
    };
  }, [speak]);

  useEffect(() => () => window.speechSynthesis?.cancel(), []);

  const activateVoice = () => {
    if (!speechAvailable) {
      setMessage("Este navegador no dispone de síntesis de voz. Puedes usar el sistema con teclado o lector de pantalla.");
      return;
    }
    setVoiceEnabled(true);
    setMessage("Guía por voz activada. Se anuncian solo acciones principales y resultados generales.");
    speak("Guía por voz activada.", true);
  };

  const deactivateVoice = () => {
    window.speechSynthesis?.cancel();
    setVoiceEnabled(false);
    setMessage("Guía por voz detenida.");
  };

  return (
    <div className="accessibility-widget">
      {open && (
        <section id="accessibility-options" className="accessibility-panel" aria-label="Opciones de accesibilidad">
          <div className="accessibility-panel-heading">
            <div>
              <strong>Accesibilidad</strong>
              <p>Color y guía por voz</p>
            </div>
            <button type="button" className="accessibility-icon-button" aria-label="Cerrar panel de accesibilidad" onClick={() => setOpen(false)}><X size={19} /></button>
          </div>
          <label htmlFor="vision-mode" className="accessibility-label">Modo de visualización</label>
          <select id="vision-mode" value={mode} onChange={(event) => setMode(event.target.value)} className="accessibility-select">
            {modes.map(([key, label]) => <option value={key} key={key}>{label}</option>)}
          </select>
          <p className="accessibility-hint">Los estados conservan texto y símbolos además del color.</p>
          <div className="accessibility-voice-actions">
            <button type="button" onClick={activateVoice} disabled={!speechAvailable || voiceEnabled} className="accessibility-voice-button"><Volume2 size={17} /> Activar guía por voz</button>
            <button type="button" onClick={deactivateVoice} disabled={!voiceEnabled} className="accessibility-stop-button"><VolumeX size={17} /> Detener voz</button>
          </div>
          {!speechAvailable && <p className="accessibility-hint" role="status">La síntesis de voz no está disponible en este dispositivo.</p>}
          {message && <p className="accessibility-status" role="status">{message}</p>}
        </section>
      )}
      <button type="button" className="accessibility-trigger" aria-expanded={open} aria-controls="accessibility-options" onClick={() => setOpen((value) => !value)}>
        <Accessibility size={20} /> <span>Accesibilidad</span>
      </button>
    </div>
  );
}
