import { Button } from "@/components/ui/button";
import { motion, useScroll, useTransform } from "framer-motion";
import { useRef, useEffect, useState } from "react";
import { ArrowRight, Play, Server, Shield, Router, CheckCircle2, Award } from "lucide-react";

// Network packet animation
const DataPacket = ({ delay, duration, path }: { delay: number; duration: number; path: string }) => (
  <motion.circle
    r="3"
    fill="hsl(38, 100%, 50%)"
    filter="url(#glow)"
    initial={{ offsetDistance: "0%" }}
    animate={{ offsetDistance: "100%" }}
    transition={{ duration, delay, repeat: Infinity, ease: "linear" }}
    style={{ offsetPath: `path('${path}')` }}
  />
);

// Animated network node
const NetworkNode = ({ x, y, delay, label }: { x: number; y: number; delay: number; label: string }) => (
  <motion.g
    initial={{ opacity: 0, scale: 0 }}
    animate={{ opacity: 1, scale: 1 }}
        transition={{ duration: 0.5, delay }}
  >
    <motion.circle
      cx={x}
      cy={y}
      r="20"
      fill="hsl(222, 47%, 15%)"
      stroke="hsl(38, 100%, 50%)"
      strokeWidth="2"
      animate={{
        boxShadow: ["0 0 0px hsl(38, 100%, 50%)", "0 0 20px hsl(38, 100%, 50%)", "0 0 0px hsl(38, 100%, 50%)"]
      }}
      transition={{ duration: 2, repeat: Infinity, delay }}
    />
    <motion.circle
      cx={x}
      cy={y}
      r="8"
      fill="hsl(38, 100%, 50%)"
      animate={{ scale: [1, 1.2, 1] }}
      transition={{ duration: 2, repeat: Infinity, delay }}
    />
    <text x={x} y={y + 35} textAnchor="middle" fill="rgba(255,255,255,0.6)" fontSize="10" fontWeight="500">
      {label}
    </text>
  </motion.g>
);

const labPreviews = [
  { name: "CCNA Enterprise", nodes: 12, status: "Disponible", icon: Router, color: "from-green-500/20 to-emerald-500/20" },
  { name: "Security Operations", nodes: 8, status: "Premium", icon: Shield, color: "from-amber/20 to-orange-500/20" },
  { name: "Data Center Core", nodes: 24, status: "Disponible", icon: Server, color: "from-blue-500/20 to-cyan-500/20" },
];


const HeroSection = () => {
  const ref = useRef<HTMLDivElement>(null);
  const [mousePosition, setMousePosition] = useState({ x: 0, y: 0 });

  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start start", "end start"],
  });

  const backgroundY = useTransform(scrollYProgress, [0, 1], [0, 200]);
  const textY = useTransform(scrollYProgress, [0, 1], [0, 80]);
  const cardsY = useTransform(scrollYProgress, [0, 1], [0, -30]);
  const opacity = useTransform(scrollYProgress, [0, 0.6], [1, 0]);

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      setMousePosition({
        x: (e.clientX / window.innerWidth - 0.5) * 20,
        y: (e.clientY / window.innerHeight - 0.5) * 20,
      });
    };
    window.addEventListener("mousemove", handleMouseMove);
    return () => window.removeEventListener("mousemove", handleMouseMove);
  }, []);

  return (
    <section
      ref={ref}
      className="relative min-h-[120vh] flex items-center justify-center overflow-hidden  z-10"
    >
      {/* Animated Network Background */}
      <motion.div
        style={{ y: backgroundY }}
        className="absolute inset-0 bg-gradient-to-b from-navy via-navy to-navy-dark"
      >
        {/* Interactive gradient that follows mouse */}
        <motion.div
          className="absolute w-[600px] h-[600px] rounded-full opacity-30"
          style={{
            background: "radial-gradient(circle, hsl(38, 100%, 50%) 0%, transparent 70%)",
            x: mousePosition.x * 2,
            y: mousePosition.y * 2,
            left: "50%",
            top: "40%",
            translateX: "-50%",
            translateY: "-50%",
          }}
        />

        {/* Network Topology SVG */}
        <svg className="absolute inset-0 w-full h-full opacity-40">
          <defs>
            <filter id="glow">
              <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
            <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stopColor="hsl(38, 100%, 50%)" stopOpacity="0"/>
              <stop offset="50%" stopColor="hsl(38, 100%, 50%)" stopOpacity="0.6"/>
              <stop offset="100%" stopColor="hsl(38, 100%, 50%)" stopOpacity="0"/>
            </linearGradient>
          </defs>

          {/* Connection Lines */}
          <motion.line x1="10%" y1="20%" x2="30%" y2="35%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 0.5 }} />
          <motion.line x1="30%" y1="35%" x2="50%" y2="25%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 0.7 }} />
          <motion.line x1="50%" y1="25%" x2="70%" y2="40%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 0.9 }} />
          <motion.line x1="70%" y1="40%" x2="90%" y2="25%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 1.1 }} />
          <motion.line x1="30%" y1="35%" x2="40%" y2="60%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 1.3 }} />
          <motion.line x1="70%" y1="40%" x2="60%" y2="65%" stroke="url(#lineGradient)" strokeWidth="1"
            initial={{ pathLength: 0 }} animate={{ pathLength: 1 }} transition={{ duration: 2, delay: 1.5 }} />

          {/* Network Nodes */}
          <NetworkNode x={100} y={150} delay={0.3} label="Router" />
          <NetworkNode x={300} y={250} delay={0.5} label="Switch" />
          <NetworkNode x={500} y={180} delay={0.7} label="Firewall" />
          <NetworkNode x={700} y={280} delay={0.9} label="Server" />
          <NetworkNode x={900} y={180} delay={1.1} label="Core" />
        </svg>

        {/* Grid Pattern */}
        <div className="absolute inset-0 opacity-[0.03]">
          <div className="absolute inset-0" style={{
            backgroundImage: `
              linear-gradient(rgba(255,191,36,1) 1px, transparent 1px),
              linear-gradient(90deg, rgba(255,191,36,1) 1px, transparent 1px)
            `,
            backgroundSize: '80px 80px',
          }} />
        </div>
      </motion.div>

      {/* Content */}
      <motion.div
        style={{ y: textY, opacity }}
        className="bg-transparent container mx-auto px-6 relative z-10 text-center pt-20"
      >
      

        {/* Badge */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.2 }}
          className="mb-8"
        >
          <span className="inline-flex items-center gap-3 px-6 py-3 bg-amber/10 backdrop-blur-md border border-amber/30 rounded-full text-sm font-medium text-amber shadow-lg shadow-amber/10">
            <motion.div
              animate={{ scale: [1, 1.3, 1], opacity: [1, 0.5, 1] }}
              transition={{ duration: 1.5, repeat: Infinity }}
              className="w-2 h-2 bg-amber rounded-full"
            />
            <span>Plateforme de confiance pour +500 ingénieurs réseau</span>
            <Award className="w-4 h-4" />
          </span>
        </motion.div>

        {/* Main Heading */}
        <motion.h1
          initial={{ opacity: 0, y: 40 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.3 }}
          className="text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-display font-bold leading-[1.05] mb-8 text-white"
        >
          <span className="block">Maîtrisez votre</span>
          <span className="relative inline-block mt-2">
            <span className="text-amber">
                Configuration réseau
            </span>
            <motion.svg
              className="absolute -bottom-4 left-0 w-full"
              viewBox="0 0 300 12"
              initial={{ pathLength: 0 }}
              animate={{ pathLength: 1 }}
              transition={{ duration: 1.5, delay: 1 }}
            >
              <motion.path
                d="M0 6 Q75 0 150 6 Q225 12 300 6"
                fill="none"
                stroke="hsl(38, 100%, 50%)"
                strokeWidth="3"
                strokeLinecap="round"
                initial={{ pathLength: 0 }}
                animate={{ pathLength: 1 }}
                transition={{ duration: 1.5, delay: 1 }}
              />
            </motion.svg>
          </span>
          <span className="block mt-2 text-white/70 text-4xl md:text-5xl lg:text-6xl">en toute confiance</span>
        </motion.h1>

        {/* Subtitle with trust elements */}
        <motion.p
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.5 }}
          className="text-lg md:text-xl text-white/60 max-w-3xl mx-auto mb-12 leading-relaxed"
        >
          La plateforme <span className="text-white font-medium">sécurisée et fiable</span> qui permet aux professionnels
          du réseau de configurer des  environnements de laboratoire{" "}
          <span className="text-amber font-semibold">en quelques secondes</span>,
          avec une garantie de disponibilité de 99.9%.
        </motion.p>

        {/* CTA Buttons with social proof */}
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.7 }}
          className="flex flex-col items-center gap-6 mb-16"
        >
          <div className="flex flex-wrap justify-center gap-4">
            <Button variant="default" size="lg" className="group shadow-xl shadow-amber/20">
              <span>Essayer</span>
              <ArrowRight className="w-5 h-5 transition-transform group-hover:translate-x-1" />
            </Button>
            <Button
              variant="outline"
              size="lg"
              className="border-white/20 text-white hover:bg-white/10 backdrop-blur-sm"
            >
              <Play className="w-5 h-5" />
              Voir la Démo (3 min)
            </Button>
          </div>

          {/* Micro trust elements */}
          <div className="flex items-center gap-6 text-white/50 text-sm">
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Sans carte bancaire
            </span>
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Setup en 2 min
            </span>
            <span className="flex items-center gap-2">
              <CheckCircle2 className="w-4 h-4 text-green-400" />
              Support 24/7
            </span>
          </div>
        </motion.div>

        {/* Lab Preview Cards */}
        <motion.div
          style={{ y: cardsY }}
          initial={{ opacity: 0, y: 60 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.9 }}
          className="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto"
        >
          {labPreviews.map((lab, index) => (
            <motion.div
              key={lab.name}
              initial={{ opacity: 0, y: 40 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 1 + index * 0.15 }}
              whileHover={{ y: -12, scale: 1.03 }}
              className="group relative bg-gradient-to-br from-white/[0.08] to-white/[0.02] backdrop-blur-xl border border-white/10 rounded-2xl p-6 cursor-pointer overflow-hidden"
            >
              {/* Animated border */}
              <motion.div
                className="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                style={{
                  background: "linear-gradient(90deg, transparent, hsl(38, 100%, 50%), transparent)",
                  backgroundSize: "200% 100%",
                }}
                animate={{
                  backgroundPosition: ["200% 0", "-200% 0"],
                }}
                transition={{ duration: 3, repeat: Infinity, ease: "linear" }}
              />

              <div className={`absolute inset-0 bg-gradient-to-br ${lab.color} opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-2xl`} />

              <div className="relative">
                <div className="flex items-start justify-between mb-4">
                  <div className="w-14 h-14 rounded-xl bg-gradient-to-br from-amber/20 to-amber/5 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <lab.icon className="w-7 h-7 text-amber" />
                  </div>
                  <span className={`px-3 py-1 rounded-full text-xs font-semibold ${
                    lab.status === "Disponible"
                      ? "bg-green-500/20 text-green-400 border border-green-500/30"
                      : "bg-amber/20 text-amber border border-amber/30"
                  }`}>
                    {lab.status}
                  </span>
                </div>

                <h3 className="text-white font-bold text-lg mb-2">{lab.name}</h3>
                <div className="flex items-center justify-between text-sm">
                  <span className="text-white/50">{lab.nodes} nœuds actifs</span>
                  <motion.span
                    className="text-amber font-medium flex items-center gap-1"
                    whileHover={{ x: 5 }}
                  >
                    Réserver →
                  </motion.span>
                </div>

                {/* Activity indicator */}
                <div className="mt-4 flex items-center gap-2">
                  <div className="flex -space-x-1">
                    {[...Array(3)].map((_, i) => (
                      <div key={i} className="w-6 h-6 rounded-full bg-gradient-to-br from-navy to-navy-light border-2 border-white/10" />
                    ))}
                  </div>
                  <span className="text-white/40 text-xs">+{Math.floor(Math.random() * 20 + 5)} utilisateurs actifs</span>
                </div>
              </div>
            </motion.div>
          ))}
        </motion.div>

        {/* Stats with trust signals */}
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ duration: 0.6, delay: 1.4 }}
          className="flex flex-wrap justify-center gap-12 md:gap-20 mt-20 pt-12 border-t border-white/10"
        >
          {[
            { value: "500+", label: "Ingénieurs actifs", subtext: "Entreprises Fortune 500" },
            { value: "99.9%", label: "Disponibilité", subtext: "SLA garanti" },
            { value: "<5s", label: "Déploiement", subtext: "Temps moyen" },
            { value: "24/7", label: "Support expert", subtext: "Temps de réponse <1h" },
          ].map((stat, index) => (
            <motion.div
              key={stat.label}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, delay: 1.5 + index * 0.1 }}
              className="text-center group"
            >
              <motion.div
                className="text-4xl md:text-5xl font-bold text-amber mb-1"
                whileHover={{ scale: 1.1 }}
              >
                {stat.value}
              </motion.div>
              <div className="text-white font-medium text-sm mb-1">{stat.label}</div>
              <div className="text-white/40 text-xs">{stat.subtext}</div>
            </motion.div>
          ))}
        </motion.div>
      </motion.div>

      {/* Bottom Wave */}
      <div className="absolute bottom-0 left-0 right-0">
        <svg className="w-full h-40" viewBox="0 0 1440 120" fill="none" preserveAspectRatio="none">
          <path
            d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H0Z"
            fill="hsl(var(--background))"
          />
        </svg>
      </div>

      {/* Scroll Indicator */}
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 2 }}
        className="absolute bottom-12 left-1/2 -translate-x-1/2"
      >
        <motion.div
          animate={{ y: [0, 8, 0] }}
          transition={{ duration: 2, repeat: Infinity }}
          className="w-6 h-10 border-2 border-white/20 rounded-full flex justify-center pt-2"
        >
          <motion.div
            animate={{ opacity: [1, 0, 1], y: [0, 8, 0] }}
            transition={{ duration: 2, repeat: Infinity }}
            className="w-1.5 h-1.5 bg-amber rounded-full"
          />
        </motion.div>
      </motion.div>
      

        {/* Date packge */}
          <motion.div
          initial={{ opacity: 0 }}
          animate={{opacity:[1 ,0,1], y:[0,8,0] }}
          >

          <DataPacket delay={0} duration={6} path="M100 150 Q200 50 300 150 T500 150 T700 150 T900 150" />
          <DataPacket delay={2} duration={5} path="M100 250 Q200 150 300 250 T500 250 T700 250 T900 250" />
          <DataPacket delay={4} duration={7} path="M100 350 Q200 250 300 350 T500 350 T700 350 T900 350" />
            
        </motion.div>
    </section>
  );
};

export default HeroSection;
