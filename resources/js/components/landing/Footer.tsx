import { motion } from "framer-motion";
import { Linkedin, Mail, MapPin, Phone } from "lucide-react";
import { SourceAppLogoIcon } from "@/media";

const footerLinks = {
  Produit: ["Fonctionnalités", "Tarifs", "Intégrations", "Changelog", "Roadmap"],
  Ressources: ["Documentation", "Tutoriels vidéo", "API Reference", "Status", "Blog"],
  Entreprise: ["À propos", "Carrières", "Partenaires", "Presse", "Contact"],
  Légal: ["Confidentialité", "CGU", "RGPD", "Sécurité", "Cookies"],
};

const socialLinks = [
    { icon: Linkedin, href: "#", label: "LinkedIn" },
];


const Footer = () => {
  return (
    <footer className="bg-navy text-white relative overflow-hidden backdrop-blur-md z-20">
      {/* Decorative top border */}
      <div className="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-amber to-transparent" />

      {/* Background pattern */}
      <div className="absolute inset-0 opacity-5">
        <div className="absolute inset-0" style={{
          backgroundImage: `radial-gradient(circle at 1px 1px, white 1px, transparent 0)`,
          backgroundSize: '40px 40px',
        }} />
      </div>

      <div className="container mx-auto px-6 py-20 relative">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-12 mb-16">
          {/* Brand Column */}
          <div className="lg:col-span-2">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              className="mb-6"
            >
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-gradient-to-br from-amber to-amber-light rounded-xl flex items-center justify-center shadow-lg shadow-amber/20">
                    <img src={SourceAppLogoIcon} alt="NetLab Logo" className="h-8 w-auto" />
                </div>
                <div>
                    <h3 className="text-2xl font-bold"><span className="bg-clip-text text-transparent bg-gradient-to-br from-accent to-ring" >Net-</span>Lab</h3>
                  <span className="text-xs text-white/40">Infrastructure as Code</span>
                </div>
              </div>
              <p className="text-white/60 text-sm leading-relaxed mb-6 max-w-sm">
                La plateforme de référence pour mettre en place votre configurartion d' infrastructures reseau .
              </p>

              {/* Contact info */}
              <div className="space-y-3 text-sm text-white/50">
                <a href="mailto:contact@netlab.io" className="flex items-center gap-2 hover:text-amber transition-colors">
                  <Mail className="w-4 h-4" />
                  contact@netlab.io
                </a>
                <div className="flex items-center gap-2">
                  <MapPin className="w-4 h-4" />
                  Abidjan, Côte d'Ivoire
                </div>
                <a href="tel:+2250701020304" className="flex items-center gap-2 hover:text-amber transition-colors">
                  <Phone className="w-4 h-4" />
                  +225 07 01 02 03 04
                </a>
              </div>
            </motion.div>


          </div>

          {/* Links Columns */}
          {Object.entries(footerLinks).map(([category, links], index) => (
            <motion.div
              key={category}
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: index * 0.1 }}
            >
              <h4 className="font-display font-bold text-white mb-5 text-sm uppercase tracking-wider">
                {category}
              </h4>
              <ul className="space-y-3">
                {links.map((link) => (
                  <li key={link}>
                    <a
                      href="#"
                      className="text-sm text-white/50 hover:text-amber transition-colors duration-200 flex items-center gap-1 group"
                    >
                      <span className="w-0 group-hover:w-2 h-px bg-amber transition-all duration-200" />
                      {link}
                    </a>
                  </li>
                ))}
              </ul>
            </motion.div>
          ))}
        </div>

        {/* Newsletter */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="border-t border-white/10 pt-12 mb-12"
        >
          <div className="flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
              <h4 className="font-display font-bold text-lg mb-1">Restez informé</h4>
              <p className="text-white/50 text-sm">Recevez les dernières mises à jour et tutoriels.</p>
            </div>
            <div className="flex gap-3 w-full md:w-auto">
              <input
                type="email"
                placeholder="votre@email.com"
                className="flex-1 md:w-64 px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white placeholder:text-white/30 focus:outline-none focus:border-amber/50 transition-colors"
              />
              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                className="px-6 py-3 bg-amber hover:bg-amber-light text-navy font-semibold rounded-xl transition-colors"
              >
                S'abonner
              </motion.button>
            </div>
          </div>
        </motion.div>

        {/* Bottom Bar */}
        <div className="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-6">
          <div className="flex items-center gap-6">
            <p className="text-sm text-white/40">
              © {new Date().getFullYear()} NetLab. Tous droits réservés.
            </p>
            <div className="hidden md:flex items-center gap-2 text-sm text-white/40">
              <span className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
              Tous les systèmes opérationnels
            </div>
          </div>

          {/* Social Links */}
          <div className="flex items-center gap-4">
            {socialLinks.map((social) => (
              <motion.a
                key={social.label}
                href={social.href}
                whileHover={{ scale: 1.1, y: -2 }}
                className="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-amber hover:border-amber hover:text-navy transition-all"
                aria-label={social.label}
                >
                <social.icon className="w-4 h-4" />
              </motion.a>
            ))}
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
