import { cn } from "@/lib/utils";
import { motion, HTMLMotionProps } from "framer-motion";
import { ReactNode, useState } from "react";

interface RippleButtonProps extends Omit<HTMLMotionProps<"button">, "children"> {
  children: ReactNode;
  variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
  size?: "default" | "sm" | "lg" | "icon";
  rippleColor?: string;
}

export function RippleButton({
  children,
  className,
  variant = "default",
  size = "default",
  rippleColor = "rgba(255, 255, 255, 0.4)",
  onClick,
  disabled,
  ...props
}: RippleButtonProps) {
  const [ripples, setRipples] = useState<Array<{ id: number; x: number; y: number }>>([]);

  const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
    if (disabled) return;

    const button = e.currentTarget;
    const rect = button.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const newRipple = {
      id: Date.now(),
      x,
      y,
    };

    setRipples(prev => [...prev, newRipple]);

    // Remove ripple after animation
    setTimeout(() => {
      setRipples(prev => prev.filter(ripple => ripple.id !== newRipple.id));
    }, 600);

    onClick?.(e);
  };

  const baseClasses = cn(
    "relative overflow-hidden inline-flex items-center justify-center gap-2 rounded-md font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
    {
      "bg-primary text-primary-foreground hover:bg-primary/90 shadow hover:shadow-lg":
        variant === "default",
      "bg-destructive text-destructive-foreground hover:bg-destructive/90 shadow hover:shadow-lg":
        variant === "destructive",
      "border border-input bg-background hover:bg-accent hover:text-accent-foreground shadow-sm hover:shadow":
        variant === "outline",
      "bg-secondary text-secondary-foreground hover:bg-secondary/80 shadow-sm hover:shadow":
        variant === "secondary",
      "hover:bg-accent hover:text-accent-foreground":
        variant === "ghost",
      "text-primary underline-offset-4 hover:underline":
        variant === "link",
    },
    {
      "h-10 px-4 py-2": size === "default",
      "h-9 rounded-md px-3": size === "sm",
      "h-11 rounded-md px-8": size === "lg",
      "h-10 w-10": size === "icon",
    },
    className
  );

  return (
    <motion.button
      className={baseClasses}
      onClick={handleClick}
      disabled={disabled}
      whileHover={{ scale: disabled ? 1 : 1.02 }}
      whileTap={{ scale: disabled ? 1 : 0.98 }}
      transition={{ duration: 0.15, ease: "easeOut" }}
      {...props}
    >
      {children}

      {/* Ripple effects */}
      {ripples.map(ripple => (
        <motion.span
          key={ripple.id}
          className="absolute pointer-events-none rounded-full opacity-75"
          style={{
            left: ripple.x - 10,
            top: ripple.y - 10,
            backgroundColor: rippleColor,
          }}
          initial={{ width: 0, height: 0, opacity: 0.6 }}
          animate={{ width: 60, height: 60, opacity: 0 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
        />
      ))}
    </motion.button>
  );
}
