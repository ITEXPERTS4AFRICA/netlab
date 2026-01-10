import { Button } from "@/components/ui/button";
import { motion, useScroll, useTransform } from "framer-motion";
import { useRef } from "react";
import { ArrowRight, Shield, Clock, Users, CheckCircle2, Star } from "lucide-react";

const testimonials = [
  {
    quote: "NetLab a transformé notre façon de former nos équipes. Déploiement instantané, zéro friction.",
    author: "Marc Dubois",
    role: "Network Architect",
    company: "Orange CI",
    rating: 5,
  },
  {
    quote: "La fiabilité est exceptionnelle. 6 mois d'utilisation, pas un seul incident.",
    author: "Sophie Kouamé",
    role: "Senior Engineer",
    company: "MTN",
    rating: 5,
  },
  {
    quote: "Le meilleur investissement pour notre équipe réseau. ROI positif en 2 semaines.",
    author: "Yves Bamba",
    role: "IT Director",
    company: "BICICI",
    rating: 5,
  },
];

const trustElements = [
  { icon: Shield, text: "Données chiffrées AES-256" },
  { icon: Clock, text: "Support réponse < 1h" },
  { icon: Users, text: "500+ entreprises" },
];

const CTASection = () => {
  const ref = useRef(null);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start end", "end start"],
  });

  const backgroundY = useTransform(scrollYProgress, [0, 1], [50, -50]);

  return (
    <section id="cta" ref={ref} className="relative py-32 overflow-hidden">
      {/* Background */}
      <motion.div 
        style={{ y: backgroundY }}
        className="absolute inset-0 bg-gradient-to-br from-navy via-navy to-navy-dark"
      >
        {/* Animated grid */}
        <div className="absolute inset-0 opacity-10">
          <div className="absolute inset-0" style={{
            backgroundImage: `
              linear-gradient(rgba(255,191,36,0.3) 1px, transparent 1px),
              linear-gradient(90deg, rgba(255,191,36,0.3) 1px, transparent 1px)
            `,
            backgroundSize: '60px 60px',
          }} />
        </div>
        
        {/* Glowing orbs */}
        <motion.div
          animate={{ 
            scale: [1, 1.2, 1],
            opacity: [0.3, 0.5, 0.3]
          }}
          transition={{ duration: 4, repeat: Infinity }}
          className="absolute top-1/4 left-1/4 w-96 h-96 bg-amber/20 rounded-full blur-[100px]"
        />
        <motion.div
          animate={{ 
            scale: [1.2, 1, 1.2],
            opacity: [0.2, 0.4, 0.2]
          }}
          transition={{ duration: 5, repeat: Infinity }}
          className="absolute bottom-1/4 right-1/4 w-72 h-72 bg-amber/10 rounded-full blur-[80px]"
        />
      </motion.div>

      <div className="container mx-auto px-6 relative z-10">
        {/* Testimonials Row */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="grid md:grid-cols-3 gap-6 mb-20"
        >
          {testimonials.map((testimonial, index) => (
            <motion.div
              key={testimonial.author}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: index * 0.1 }}
              whileHover={{ y: -5, scale: 1.02 }}
              className="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:border-amber/30 transition-all"
            >
              {/* Rating */}
              <div className="flex gap-1 mb-4">
                {[...Array(testimonial.rating)].map((_, i) => (
                  <Star key={i} className="w-4 h-4 fill-amber text-amber" />
                ))}
              </div>
              
              <p className="text-white/80 text-sm leading-relaxed mb-4 italic">
                "{testimonial.quote}"
              </p>
              
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-amber to-amber-light flex items-center justify-center text-navy font-bold text-sm">
                  {testimonial.author[0]}
                </div>
                <div>
                  <div className="text-white font-medium text-sm">{testimonial.author}</div>
                  <div className="text-white/50 text-xs">{testimonial.role} • {testimonial.company}</div>
                </div>
              </div>
            </motion.div>
          ))}
        </motion.div>

        {/* Main CTA */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="text-center max-w-4xl mx-auto"
        >
          {/* Badge */}
          <motion.div
            animate={{ scale: [1, 1.02, 1] }}
            transition={{ duration: 3, repeat: Infinity }}
            className="inline-flex items-center gap-2 px-5 py-2.5 bg-amber/20 border border-amber/40 rounded-full mb-8"
          >
            <motion.span
              animate={{ rotate: 360 }}
              transition={{ duration: 3, repeat: Infinity, ease: "linear" }}
            >
              ⚡
            </motion.span>
            <span className="text-sm font-semibold text-amber">
              Offre de lancement - 30% de réduction
            </span>
          </motion.div>

          {/* Headline */}
          <h2 className="text-4xl md:text-5xl lg:text-6xl font-display font-bold text-white mb-6 leading-tight">
            Rejoignez les leaders
            <br />
            <span className="text-amber">du réseau africain</span>
          </h2>

          {/* Subheadline */}
          <p className="text-lg md:text-xl text-white/70 max-w-2xl mx-auto mb-10">
            Transformez votre équipe réseau avec la plateforme qui a déjà convaincu 
            <span className="text-white font-semibold"> +500 professionnels certifiés</span>.
          </p>

          {/* CTA Buttons */}
          <div className="flex flex-col sm:flex-row justify-center gap-4 mb-10">
            <motion.div whileHover={{ scale: 1.02 }} whileTap={{ scale: 0.98 }}>
              <Button
                size="xl"
                className="bg-amber hover:bg-amber-light text-navy font-bold shadow-xl shadow-amber/30 w-full sm:w-auto"
              >
                Démarrer l'essai gratuit
                <ArrowRight className="w-5 h-5" />
              </Button>
            </motion.div>
            <Button
              size="xl"
              variant="outline"
              className="border-white/30 text-white hover:bg-white/10 w-full sm:w-auto"
            >
              Planifier une démo
            </Button>
          </div>

          {/* Trust Elements */}
          <div className="flex flex-wrap justify-center gap-6 mb-8">
            {trustElements.map((element) => (
              <div key={element.text} className="flex items-center gap-2 text-white/60 text-sm">
                <element.icon className="w-4 h-4 text-amber" />
                {element.text}
              </div>
            ))}
          </div>

          {/* Final trust line */}
          <div className="flex items-center justify-center gap-4 text-white/50 text-sm">
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Sans engagement
            </span>
            <span className="w-1 h-1 bg-white/30 rounded-full" />
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Annulation en 1 clic
            </span>
            <span className="w-1 h-1 bg-white/30 rounded-full" />
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Support inclus
            </span>
          </div>
        </motion.div>
      </div>
    </section>
  );
};

export default CTASection;
