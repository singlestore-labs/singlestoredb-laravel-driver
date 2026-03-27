#!/usr/bin/env bash
set -euxo pipefail

echo "=== BEFORE ==="
df -h

sudo rm -rf /usr/share/dotnet
sudo rm -rf /opt/ghc
sudo rm -rf /usr/local/share/boost
sudo rm -rf "$AGENT_TOOLSDIRECTORY"
sudo rm -rf /usr/local/lib/android
sudo rm -rf /opt/hostedtoolcache/CodeQL
sudo rm -rf /opt/hostedtoolcache/Ruby
sudo rm -rf /opt/hostedtoolcache/Go

sudo rm -rf /opt/hostedtoolcache/Java*
sudo rm -rf /opt/hostedtoolcache/Python*
sudo rm -rf /opt/hostedtoolcache/Node*
sudo rm -rf /opt/hostedtoolcache/PyPy*
sudo rm -rf /opt/hostedtoolcache/Swift*
sudo rm -rf /opt/hostedtoolcache/gradle*
sudo rm -rf /opt/hostedtoolcache/maven*
sudo rm -rf /opt/hostedtoolcache/Rust*
sudo rm -rf /opt/hostedtoolcache/Perl*
sudo rm -rf /opt/hostedtoolcache/llvm

sudo rm -rf /usr/local/lib/node_modules || true
sudo rm -rf /usr/local/share/powershell || true
sudo rm -rf /usr/local/julia* || true
sudo rm -rf /usr/share/swift || true

sudo rm -rf /var/lib/apt/lists/* || true
sudo rm -rf /var/cache/apt/* || true
sudo rm -rf /var/cache/man/* || true
sudo rm -rf /var/log/* || true
sudo rm -rf /tmp/* || true

sudo apt-get update || true
sudo apt-get purge -y \
    azure-cli \
    google-cloud-cli \
    google-chrome-stable \
    firefox \
    powershell \
    mono-devel \
    hhvm \
    php* \
    dotnet-sdk-* \
    temurin-* \
    openjdk-* \
    mysql-client* \
    postgresql-client* \
    || true

sudo apt-get autoremove -y || true
sudo apt-get clean || true

docker system prune -af || true
docker volume prune -f || true

echo "=== AFTER ==="
df -h
