import { motion, useInView } from "framer-motion";
import { useRef } from "react";
import { Zap, Activity, Shield, Server, Clock, Lock, CheckCircle, TrendingUp, Users } from "lucide-react";

const features = [
  {
    icon: Zap,
    title: "Déploiement Ultra-Rapide",
    description:
      "Lancez des topologies complexes OSPF, BGP, SDA en un clic. Notre moteur d'automatisation élimine les heures de configuration manuelle.",
    highlight: "10x plus rapide",
    stats: "Réduction de 90% du temps de setup",
    gradient: "from-yellow-500/20 via-amber/10 to-transparent",
  },
  {
    icon: Activity,
    title: "Monitoring Intelligent",
    description:
      "Visualisation temps réel de la charge CPU, RAM, latence. Alertes prédictives avant que les problèmes ne surviennent.",
    highlight: "0% downtime",
    stats: "Détection proactive des anomalies",
    gradient: "from-green-500/20 via-emerald-500/10 to-transparent",
  },
  {
    icon: Shield,
    title: "Sécurité Enterprise",
    description:
      "Isolation complète de chaque lab. Environnements sandboxés avec chiffrement AES-256. Conformité SOC 2 et ISO 27001.",
    highlight: "100% sécurisé",
    stats: "Certification enterprise-grade",
    gradient: "from-blue-500/20 via-cyan-500/10 to-transparent",
  },
];

const trustIndicators = [
  { icon: Server, label: "Multi-cluster", value: "99.99%" },
  { icon: Clock, label: "Scheduling avancé", value: "24/7" },
  { icon: Lock, label: "SSO/LDAP", value: "Enterprise" },
  { icon: Users, label: "Collaboration", value: "Temps réel" },
];

const FeaturesSection = () => {
  const ref = useRef(null);
  const isInView = useInView(ref, { once: true, margin: "-100px" });

  return (
    <section id="features" ref={ref} className="py-32 bg-background/10 relative z-2 backdrop-blur-xs overflow-hidden">
      {/* Background decoration */}
      <div className="absolute top-0 right-0 w-1/2 h-1/2 bg-gradient-to-bl from-amber/5 to-transparent rounded-full blur-3xl" />
      <div className="absolute bottom-0 left-0 w-1/3 h-1/3 bg-gradient-to-tr from-navy/5 to-transparent rounded-full blur-3xl" />
      
      <div className="container mx-auto px-6 relative">
        {/* Section Header */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6 }}
          className="text-center mb-20"
        >
          <motion.span 
            initial={{ opacity: 0 }}
            animate={isInView ? { opacity: 1 } : {}}
            className="inline-flex items-center gap-2 px-4 py-2 bg-amber/10 border border-amber/20 rounded-full text-sm font-medium text-amber mb-6"
          >
            <TrendingUp className="w-4 h-4" />
            Fonctionnalités Premium
          </motion.span>
          
          <h2 className="text-4xl md:text-5xl font-display font-bold text-foreground mb-6">
            Pourquoi les{" "}
            <span className="relative inline-block">
              <span className="text-amber">experts</span>
              <motion.span
                initial={{ scaleX: 0 }}
                animate={isInView ? { scaleX: 1 } : {}}
                transition={{ duration: 0.8, delay: 0.5 }}
                className="absolute -bottom-2 left-0 right-0 h-1 bg-amber rounded-full origin-left"
              />
            </span>{" "}
            nous font confiance
          </h2>
          <p className="text-muted-foreground text-lg max-w-2xl mx-auto">
            Une plateforme conçue par des ingénieurs réseau certifiés, 
            pour des professionnels qui exigent l'excellence.
          </p>
        </motion.div>

        {/* Feature Cards */}
        <div className="grid md:grid-cols-3 gap-8 mb-16">
          {features.map((feature, index) => (
            <motion.div
              key={feature.title}
              initial={{ opacity: 0, y: 40 }}
              animate={isInView ? { opacity: 1, y: 0 } : {}}
              transition={{ duration: 0.6, delay: index * 0.15 }}
              className="group relative"
            >
              <div className="h-full p-8 rounded-3xl bg-card border border-border hover:border-amber/50 transition-all duration-500 hover:shadow-2xl hover:shadow-amber/10 relative overflow-hidden">
                {/* Gradient background on hover */}
                <div className={`absolute inset-0 bg-gradient-to-br ${feature.gradient} opacity-0 group-hover:opacity-100 transition-opacity duration-500`} />
                
                <div className="relative">
                  {/* Icon with glow effect */}
                  <motion.div 
                    className="w-16 h-16 rounded-2xl bg-gradient-to-br from-navy/10 to-navy/5 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300"
                    whileHover={{ rotate: [0, -5, 5, 0] }}
                    transition={{ duration: 0.5 }}
                  >
                    <feature.icon className="w-8 h-8 text-navy group-hover:text-amber transition-colors duration-300" />
                  </motion.div>

                  {/* Highlight Badge */}
                  <span className="inline-flex items-center gap-1 px-3 py-1.5 bg-amber/10 text-amber text-xs font-bold rounded-full mb-4 border border-amber/20">
                    <CheckCircle className="w-3 h-3" />
                    {feature.highlight}
                  </span>

                  {/* Title */}
                  <h3 className="text-xl font-display font-bold text-foreground mb-3">
                    {feature.title}
                  </h3>

                  {/* Description */}
                  <p className="text-muted-foreground leading-relaxed mb-4">
                    {feature.description}
                  </p>

                  {/* Stats line */}
                  <div className="pt-4 border-t border-border/50">
                    <span className="text-sm text-foreground/70 flex items-center gap-2">
                      <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                      {feature.stats}
                    </span>
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Trust Indicators Grid */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={isInView ? { opacity: 1, y: 0 } : {}}
          transition={{ duration: 0.6, delay: 0.6 }}
          className="grid grid-cols-2 md:grid-cols-4 gap-4"
        >
          {trustIndicators.map((item, index) => (
            <motion.div
              key={item.label}
              initial={{ opacity: 0, scale: 0.9 }}
              animate={isInView ? { opacity: 1, scale: 1 } : {}}
              transition={{ duration: 0.4, delay: 0.7 + index * 0.1 }}
              whileHover={{ y: -5, scale: 1.02 }}
              className="flex items-center gap-4 p-5 bg-secondary/50 backdrop-blur-sm rounded-2xl border border-border/50 hover:border-amber/30 transition-all"
            >
              <div className="w-12 h-12 rounded-xl bg-amber/10 flex items-center justify-center">
                <item.icon className="w-6 h-6 text-amber" />
              </div>
              <div>
                <div className="font-bold text-foreground">{item.value}</div>
                <div className="text-sm text-muted-foreground">{item.label}</div>
              </div>
            </motion.div>
          ))}
        </motion.div>
      </div>
    </section>
  );
};

export default FeaturesSection;
