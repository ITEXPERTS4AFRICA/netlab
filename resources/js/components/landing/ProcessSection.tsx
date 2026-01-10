import { motion, useInView, useScroll, useTransform } from "framer-motion";
import { useRef } from "react";
import { CheckCircle, Network, Rocket, Shield, BarChart3, ArrowRight } from "lucide-react";

const steps = [
  {
    number: "01",
    title: "Sélection du Template",
    description:
      "Choisissez parmi nos topologies pré-configurées (OSPF, BGP, SDA, SDWAN) ou créez la vôtre. Réservation instantanée.",
    benefits: ["+50 templates disponibles", "Configuration personnalisable"],
    icon: Network,
    color: "from-blue-500 to-cyan-500",
  },
  {
    number: "02",
    title: "Déploiement Automatisé",
    description:
      "NetLab orchestre l'API Cisco CML. Vos nœuds démarrent, les configurations de base sont injectées automatiquement.",
    benefits: ["Zero intervention", "Provisioning < 30 sec"],
    icon: Rocket,
    color: "from-amber to-orange-500",
  },
  {
    number: "03",
    title: "Accès Sécurisé",
    description:
      "Tunnel VPN dédié créé automatiquement. Accès console SSH/Telnet et management IP pour vos tests en isolation totale.",
    benefits: ["VPN chiffré AES-256", "Credentials uniques"],
    icon: Shield,
    color: "from-green-500 to-emerald-500",
  },
  {
    number: "04",
    title: "Analytics & Rapport",
    description:
      "À la fin du créneau, le lab est détruit proprement. Rapport d'activité détaillé généré dans votre dashboard.",
    benefits: ["Métriques complètes", "Export PDF/CSV"],
    icon: BarChart3,
    color: "from-purple-500 to-pink-500",
  },
];

const ProcessSection = () => {
  const containerRef = useRef(null);
  const isInView = useInView(containerRef, { once: true, margin: "-100px" });
  
  const { scrollYProgress } = useScroll({
    target: containerRef,
    offset: ["start end", "end start"],
  });

  const lineProgress = useTransform(scrollYProgress, [0.1, 0.9], [0, 100]);

  return (
    <section
      id="process"
      ref={containerRef}
      className="py-32 backdrop-blur-xs bg-gradient-to-b from-secondary/50 to-transparent relative overflow-hidden z-2"
    >
      {/* Animated network background */}
      <div className="absolute inset-0 opacity-[0.03]">
        <svg className="w-full h-full">
          <pattern id="networkPattern" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
            <circle cx="50" cy="50" r="1" fill="currentColor" className="text-navy" />
            <line x1="50" y1="50" x2="100" y2="50" stroke="currentColor" strokeWidth="0.5" className="text-navy" />
            <line x1="50" y1="50" x2="50" y2="100" stroke="currentColor" strokeWidth="0.5" className="text-navy" />
          </pattern>
          <rect width="100%" height="100%" fill="url(#networkPattern)" />
        </svg>
      </div>

      <div className="container mx-auto px-6 relative z-10">
        {/* Section Header */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6 }}
          className="text-center mb-20"
        >
          <motion.span 
            className="inline-flex items-center gap-2 px-4 py-2 bg-navy/5 border border-navy/10 rounded-full text-sm font-medium text-navy mb-6"
          >
            <ArrowRight className="w-4 h-4" />
            Processus simplifié
          </motion.span>
          
          <h2 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-6">
            Du concept au{" "}
            <span className="relative inline-block">
              <span className="text-amber">déploiement</span>
              <motion.span
                initial={{ scaleX: 0 }}
                animate={isInView ? { scaleX: 1 } : {}}
                transition={{ duration: 0.8, delay: 0.5 }}
                className="absolute -bottom-2 left-0 right-0 h-1 bg-amber rounded-full origin-left"
              />
            </span>
          </h2>
          <p className="text-muted-foreground text-lg max-w-2xl mx-auto">
            4 étapes. Automatisé. Transparent. 
            <span className="text-foreground font-medium"> Conçu pour l'efficacité.</span>
          </p>
        </motion.div>

        {/* Timeline */}
        <div className="relative max-w-5xl mx-auto">
          {/* Animated Progress Line */}
          <div className="hidden lg:block absolute top-[60px] left-0 right-0 h-1 bg-border rounded-full overflow-hidden">
            <motion.div
              style={{ width: `${lineProgress.get()}%` }}
              className="h-full bg-gradient-to-r from-amber via-amber to-amber/50 rounded-full"
            />
          </div>

          {/* Steps */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
            {steps.map((step, index) => (
              <motion.div
                key={step.number}
                initial={{ opacity: 0, y: 40 }}
                animate={isInView ? { opacity: 1, y: 0 } : {}}
                transition={{ duration: 0.6, delay: 0.2 + index * 0.15 }}
                className="relative group"
              >
                {/* Number Badge */}
                <div className="flex justify-center lg:justify-start mb-8">
                  <motion.div
                    whileHover={{ scale: 1.1, rotate: 5 }}
                    className={`w-16 h-16 rounded-2xl bg-gradient-to-br ${step.color} text-white font-display font-bold text-xl flex items-center justify-center shadow-lg relative z-10`}
                  >
                    <step.icon className="w-7 h-7" />
                  </motion.div>
                </div>

                {/* Content Card */}
                <motion.div 
                  whileHover={{ y: -5 }}
                  className="bg-card rounded-2xl p-6 border border-border hover:border-amber/30 hover:shadow-xl transition-all duration-300"
                >
                  <span className="text-xs font-bold text-amber mb-2 block">ÉTAPE {step.number}</span>
                  <h3 className="text-lg font-display font-bold text-foreground mb-3">
                    {step.title}
                  </h3>
                  <p className="text-muted-foreground text-sm leading-relaxed mb-4">
                    {step.description}
                  </p>

                  {/* Benefits */}
                  <div className="space-y-2">
                    {step.benefits.map((benefit) => (
                      <div
                        key={benefit}
                        className="flex items-center gap-2"
                      >
                        <CheckCircle className="w-4 h-4 text-green-500 flex-shrink-0" />
                        <span className="text-sm text-foreground/80">
                          {benefit}
                        </span>
                      </div>
                    ))}
                  </div>
                </motion.div>
              </motion.div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
export default ProcessSection;
