import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { LucideIcon } from "lucide-react";
import { motion } from "framer-motion";

interface CardMetricProps {
    title: string;
    value: number | string;
    description?: string;
    icon?: LucideIcon;
    trend?: {
        value: number;
        isPositive: boolean;
    };
    color?: "primary" | "secondary" | "success" | "warning" | "destructive";
    className?: string;
}

const colorClasses = {
    primary: "text-primary bg-primary/10 border-primary/20",
    secondary: "text-secondary-foreground bg-secondary/10 border-secondary/20",
    success: "text-green-600 bg-green-100 border-green-200",
    warning: "text-yellow-600 bg-yellow-100 border-yellow-200",
    destructive: "text-destructive bg-destructive/10 border-destructive/20",
};

export function CardMetric({
    title,
    value,
    description,
    icon: Icon,
    trend,
    color = "primary",
    className = "",
}: CardMetricProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            whileHover={{ scale: 1.02 }}
            className={className}
        >
            <Card className={`relative overflow-hidden border-2 transition-all duration-200 hover:shadow-lg ${colorClasses[color]}`}>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">
                        {title}
                    </CardTitle>
                    {Icon && (
                        <div className={`p-2 rounded-lg ${colorClasses[color]}`}>
                            <Icon className="h-4 w-4" />
                        </div>
                    )}
                </CardHeader>
                <CardContent>
                    <div className="flex items-center justify-between">
                        <div className="text-2xl font-bold">{value}</div>
                        {trend && (
                            <Badge
                                variant={trend.isPositive ? "default" : "destructive"}
                                className="text-xs"
                            >
                                {trend.isPositive ? "+" : ""}{trend.value}%
                            </Badge>
                        )}
                    </div>
                    {description && (
                        <p className="text-xs text-muted-foreground mt-1">
                            {description}
                        </p>
                    )}
                </CardContent>
            </Card>
        </motion.div>
    );
}
