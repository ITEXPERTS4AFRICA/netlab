#!/bin/bash

echo "🔍 Diagnostic réseau approfondi"
echo "================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() { echo -e "${GREEN}ℹ️  $1${NC}"; }
warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }

# 1. Vérifier l'interface réseau
info "1. Informations sur l'interface réseau ens160:"
echo "----------------------------------------------"
ip addr show ens160
echo ""

# 2. Vérifier si une IP est assignée
IP_ADDR=$(ip addr show ens160 | grep -oP 'inet \K[\d.]+' | head -n1)
if [ -z "$IP_ADDR" ]; then
    error "❌ Aucune adresse IP assignée à ens160"
    NO_IP=true
else
    success "✅ Adresse IP: $IP_ADDR"
    NO_IP=false
fi
echo ""

# 3. Vérifier la route par défaut
info "2. Routes configurées:"
echo "----------------------"
ip route show
echo ""

GATEWAY=$(ip route show | grep default | awk '{print $3}')
if [ -z "$GATEWAY" ]; then
    error "❌ Aucune route par défaut (gateway) configurée"
    NO_GATEWAY=true
else
    success "✅ Gateway: $GATEWAY"
    NO_GATEWAY=false
fi
echo ""

# 4. Vérifier la connexion NetworkManager
info "3. État de la connexion NetworkManager:"
echo "---------------------------------------"
nmcli connection show "Connexion filaire 1" | grep -E "connection|ipv4|state"
echo ""

# 5. Tester la connectivité
info "4. Test de connectivité:"
echo "------------------------"
if timeout 3 ping -c 1 8.8.8.8 > /dev/null 2>&1; then
    success "✅ Connexion Internet OK"
    exit 0
else
    error "❌ Pas de connexion Internet"
fi
echo ""

# 6. Diagnostic et recommandations
info "5. Diagnostic:"
echo "--------------"

if [ "$NO_IP" = true ]; then
    warn "PROBLÈME: Aucune adresse IP assignée"
    echo ""
    echo "SOLUTIONS à essayer:"
    echo ""
    echo "Solution 1: Activer la connexion et forcer DHCP"
    echo "  sudo nmcli connection up 'Connexion filaire 1'"
    echo "  sudo dhclient ens160"
    echo ""
    echo "Solution 2: Configurer via nmtui (interface graphique)"
    echo "  sudo nmtui"
    echo ""
    echo "Solution 3: Configurer manuellement via NetworkManager"
    echo "  sudo nmcli connection modify 'Connexion filaire 1' ipv4.method auto"
    echo "  sudo nmcli connection up 'Connexion filaire 1'"
    echo ""
fi

if [ "$NO_GATEWAY" = true ]; then
    warn "PROBLÈME: Aucune route par défaut configurée"
    echo ""
    echo "Cela signifie que même avec une IP, vous ne pouvez pas accéder à Internet"
    echo "Si vous êtes sur une machine virtuelle, vérifiez les paramètres réseau"
    echo ""
fi

# 7. Informations spécifiques aux machines virtuelles
info "6. Note pour machines virtuelles:"
echo "----------------------------------"
echo "Si vous êtes sur VirtualBox ou VMware:"
echo "  1. Vérifiez que l'adaptateur réseau est activé dans la VM"
echo "  2. Mode réseau: NAT ou Réseau NAT (recommandé pour Internet)"
echo "  3. Vérifiez que l'hôte a une connexion Internet"
echo "  4. Essayez de redémarrer l'interface dans la VM:"
echo "     sudo ip link set ens160 down && sudo ip link set ens160 up"
echo ""

# 8. Script de correction automatique
info "7. Tenter une correction automatique..."
echo "----------------------------------------"

# Réessayer d'activer la connexion
if nmcli connection show "Connexion filaire 1" | grep -q "STATE.*activated"; then
    info "Connexion déjà activée, tentative de réactivation..."
    sudo nmcli connection down "Connexion filaire 1" 2>/dev/null || true
    sleep 1
fi

sudo nmcli connection up "Connexion filaire 1"
sleep 2

# Essayer dhclient
info "Tentative d'obtention d'une IP via DHCP..."
sudo dhclient -v ens160 2>&1 | head -n 10 || warn "dhclient a échoué"

sleep 3

# Vérifier à nouveau
echo ""
info "Nouveau diagnostic:"
IP_ADDR_NEW=$(ip addr show ens160 | grep -oP 'inet \K[\d.]+' | head -n1)
GATEWAY_NEW=$(ip route show | grep default | awk '{print $3}')

if [ -n "$IP_ADDR_NEW" ]; then
    success "Adresse IP obtenue: $IP_ADDR_NEW"
else
    error "Toujours pas d'adresse IP"
fi

if [ -n "$GATEWAY_NEW" ]; then
    success "Gateway configuré: $GATEWAY_NEW"
else
    warn "Toujours pas de gateway"
fi

echo ""
if timeout 3 ping -c 1 8.8.8.8 > /dev/null 2>&1; then
    success "✅ CONNEXION INTERNET RESTAURÉE !"
    exit 0
else
    error "❌ Connexion Internet toujours indisponible"
    echo ""
    warn "PROCHAINES ÉTAPES MANUELLES:"
    echo ""
    echo "1. Si vous êtes sur une VM, vérifiez les paramètres réseau de la VM"
    echo "2. Essayez nmtui pour configurer manuellement:"
    echo "   sudo nmtui"
    echo "3. Ou configurez une IP statique si vous connaissez les paramètres réseau"
    exit 1
fi

