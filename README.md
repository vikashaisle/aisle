# aisle virtualbox copy & Drag and Drop

mkdir -p /mnt/cdrom
mount /dev/cdrom /mnt/cdrom
cd /mnt/cdrom
./VBoxLinuxAdditions.run

copy text from my Windows host but cannot paste it into the VirtualBox VM


Charted troubleshooting pathway for clipboard configuration issues
Charted troubleshooting pathway for clipboard configuration issues
That's almost always a shared clipboard / Guest Additions issue. Here's how to fix it, in order of likelihood:

1. Check Shared Clipboard is actually enabled
With the VM running, go to the VM window menu: Devices → Shared Clipboard
Make sure it's set to Bidirectional (not "Host to Guest" only being blocked, or "Disabled")
Also check Devices → Drag and Drop → Bidirectional while you're there (separate feature, often needed alongside)
2. Verify Guest Additions are installed and running
This is the #1 cause — clipboard sharing does not work without Guest Additions installed inside the Linux VM.

Check inside the VM:

bash ##************************************##

**lsmod | grep vboxguest**

If this returns nothing, Guest Additions either aren't installed or aren't loaded. Install them:

bash ##************************************##

**sudo dnf install -y gcc make perl elfutils-libelf-devel kernel-devel-$(uname -r) bzip2**

Then in the VM window menu: Devices → Insert Guest Additions CD image #####################

bash ##************************************##

**sudo mount /dev/cdrom /mnt**
**sudo /mnt/VBoxLinuxAdditions.run**
**sudo reboot**

3. Restart the clipboard service inside the guest (if Additions are already installed)
Sometimes the service just needs a kick:

bash       ##************************************##

**sudo systemctl restart vboxadd-service**

Or if using a GUI desktop, log out and back in — VBoxClient (the clipboard helper) sometimes needs to restart with the desktop session.

4. Confirm VBoxClient is running (GUI desktop installs only)
bash   ##************************************##

**ps aux | grep VBoxClient**

You should see VBoxClient --clipboard in the list. If it's missing, start it manually:

bash ##************************************##

**VBoxClient --clipboard**

5. If you're on a Minimal/no-GUI install
Clipboard sharing requires a graphical session running inside the guest — there's no clipboard concept in a pure terminal/console. If you're working via SSH (PuTTY/Windows Terminal), copy-paste already works normally through the terminal itself (right-click paste in PuTTY, or Ctrl+Shift+V in Windows Terminal) — no VirtualBox clipboard setting applies there at all.

6. Last resort: toggle it off and on again
Sometimes the setting itself gets stuck:

Devices → Shared Clipboard → Disabled
Wait a few seconds
Devices → Shared Clipboard → Bidirectional again
Quick question to narrow this down: are you running a GUI desktop in the Rocky Linux VM, or connecting via SSH/terminal? The fix is different depending on which.

Install Software
============================

# Rocky Linux 8 VM Deployment — Step-by-Step Process

Based on the internal configuration document. Covers: VM setup → OS hardening → Ruby/Rails/Nginx stack → PostgreSQL → Jenkins CI/CD → verification.

---

## Phase 1: Create and Prepare the VM

1. Create a new VM running Rocky Linux 8.x (replaces older CentOS 7.x VMs per the OS migration mandate).
2. Set SELinux to disabled mode:
   ```bash
   vi /etc/sysconfig/selinux
   # set: SELINUX=disabled
   ```
3. Update the OS and reboot:
   ```bash
   dnf update -y
   reboot
   ```
4. Set up automatic security updates:
   ```bash
   dnf install dnf-automatic
   vi /etc/dnf/automatic.conf
   # set: upgrade_type = security
   # set: apply_updates = yes
   systemctl enable --now dnf-automatic.timer
   ```
   Non-security updates are applied manually on a periodic basis.
5. Create a user account, install an SSH public key for authentication, and grant `sudo` access where required.
   > **Important:** Test SSH login with this new account *before* locking down SSH config, to avoid getting locked out.
6. Whitelist Cloudflare IPs via `iptables` so only Cloudflare's reverse proxy can reach ports 80/443. Current IP list: https://www.cloudflare.com/en-in/ips/
7. Install build dependencies for the Ruby/Rails environment:
   ```bash
   dnf group install "Development Tools"
   dnf install epel-release
   dnf install dnf-plugins-core
   dnf config-manager --set-enabled powertools
   dnf clean all
   dnf update
   dnf install libffi-devel libyaml-devel
   ```
8. Raise the file descriptor limit — add to `/etc/security/limits.conf`:
   ```
   * soft nofile 10240
   * hard nofile 102400
   ```
   Log out and back in for this to take effect.

---

## Phase 2: Secure SSH

1. Edit `/etc/ssh/sshd_config` and set:
   ```
   PermitRootLogin no
   MaxAuthTries 3
   PasswordAuthentication no
   X11Forwarding no
   ```
2. Restart the SSH daemon:
   ```bash
   systemctl restart sshd
   ```

---

## Phase 3: Configure the Web Server (Nginx + Passenger + Ruby/Rails)

1. Add the Passenger repo:
   ```bash
   curl --fail -sSLo /etc/yum.repos.d/passenger.repo \
     https://ossbinaries.phusionpassenger.com/yum/definitions/el-passenger.repo
   dnf update
   ```
2. Enable and install Nginx with the Passenger module:
   ```bash
   dnf module list nginx
   dnf module reset nginx -y
   dnf module enable nginx:1.22 -y
   dnf install nginx nginx-mod-http-passenger
   ```
3. Create a dedicated deployment user and switch to it:
   ```bash
   adduser -c "Deployment" deployment
   su - deployment
   ```
4. Install `rbenv` and `ruby-build` (as the `deployment` user):
   ```bash
   git clone https://github.com/rbenv/rbenv.git ~/.rbenv
   echo 'export PATH="$HOME/.rbenv/bin:$PATH"' >> ~/.bashrc
   echo 'eval "$(rbenv init -)"' >> ~/.bashrc
   source ~/.bashrc
   git clone https://github.com/rbenv/ruby-build.git ~/.rbenv/plugins/ruby-build
   ```
5. Install Ruby 3.3.0:
   ```bash
   rbenv install 3.3.0
   rbenv global 3.3.0
   ruby -v   # verify
   ```
6. Install Bundler and Rails 6.1.7:
   ```bash
   echo "gem: --no-document" > ~/.gemrc
   gem install bundler
   gem install rails -v 6.1.7
   rbenv rehash
   rails -v   # verify
   ```
7. Add Nginx hardening config at `/etc/nginx/conf.d/security.conf` (server tokens off, TLS 1.2, strong cipher list, security headers — HSTS, X-Frame-Options, CSP, etc. as specified in the source document).
8. Configure Passenger at `/etc/nginx/conf.d/passenger.conf`, pointing `passenger_ruby` to the rbenv Ruby binary for the `deployment` user, and setting pool/queue sizing (`passenger_max_pool_size 16`, `passenger_min_instances 2`, etc.).
9. Create the virtual host config at `/etc/nginx/conf.d/aisle.conf` — one `server` block for port 80 and one for port 443 (SSL, HTTP/2), both pointing to the app's `public/` directory with Passenger enabled and the production Rails environment set. Reference the source document for the exact block content, including the Cloudflare Origin certificate paths.
   > Note: SSL terminates at Cloudflare (Aisle uses it as a proxy), so the certs used here are Cloudflare Origin certificates generated from the Cloudflare console.
10. Test and restart Nginx:
    ```bash
    nginx -t
    systemctl restart nginx
    ```

---

## Phase 4: Install and Configure PostgreSQL 13

1. Disable the OS-default PostgreSQL module:
   ```bash
   dnf module list postgresql
   dnf -qy module disable postgresql
   ```
2. Add the PGDG repository and install PostgreSQL 13:
   ```bash
   dnf install -y https://download.postgresql.org/pub/repos/yum/reporpms/EL-8-x86_64/pgdg-redhat-repo-latest.noarch.rpm
   dnf -y update
   dnf -y install postgresql13-server
   /usr/pgsql-13/bin/postgresql-13-setup initdb
   systemctl start postgresql-13
   systemctl enable postgresql-13
   ```
3. Configure client access in `/var/lib/pgsql/13/data/pg_hba.conf`:
   ```
   host all all 127.0.0.1/32 md5
   host all all 10.139.0.0/16 md5
   hostssl all all 10.139.0.0/16 md5 clientcert=verify-ca
   ```
   Then:
   ```bash
   systemctl restart postgresql-13
   ```
4. Tune PostgreSQL using pgtune (http://pgtune.leopard.in.ua/) and apply the generated settings to `/var/lib/pgsql/13/data/postgresql.conf`, then restart.
5. Generate a self-signed CA and server certificate for PostgreSQL SSL:
   ```bash
   openssl req -new -x509 -days 365 -nodes -text -out server.crt -keyout server.key -subj "/CN=proddb.aisle.co"
   chmod og-rwx server.key
   openssl req -new -nodes -text -out root.csr -keyout root.key -subj "/CN=proddb.aisle.co"
   chmod og-rwx root.key
   openssl x509 -req -in root.csr -text -days 3650 -extfile /etc/pki/tls/openssl.cnf -extensions v3_ca -signkey root.key -out root.crt
   openssl req -new -nodes -text -out server.csr -keyout server.key -subj "/CN=proddb.aisle.co"
   openssl x509 -req -in server.csr -text -days 365 -CA root.crt -CAkey root.key -CAcreateserial -out server.crt
   ```
   Store `root.crt`, `server.crt`, and `server.key` in the directory referenced by `postgresql.conf` — **never in `/root`**, PostgreSQL cannot read from there. Keep `root.key` offline for future certificate generation. `root.crt` should also be distributed to clients so they can verify the server certificate.
6. Enable SSL in `/var/lib/pgsql/13/data/postgresql.conf`:
   ```
   ssl = on
   ssl_ca_file = '/etc/ssl/postgresql/root.crt'
   ssl_cert_file = '/etc/ssl/postgresql/server.crt'
   ssl_key_file = '/etc/ssl/postgresql/server.key'
   ssl_ciphers = 'HIGH:+3DES:!aNULL'
   ssl_prefer_server_ciphers = on
   ssl_ecdh_curve = 'prime256v1'
   ssl_min_protocol_version = 'TLSv1.2'
   ```
7. Restart PostgreSQL:
   ```bash
   systemctl restart postgresql-13
   ```

---

## Phase 5: Deployment Preparation

1. Set correct ownership on the app directory:
   ```bash
   chown deployment: /var/www/html/aisle
   ```
2. Copy `root.crt` (from the PostgreSQL SSL setup) to `/home/deployment/.postgresql/` so the app can connect to the database over SSL.

---

## Phase 6: Install and Configure Jenkins (CI/CD)

1. Install Java and Jenkins:
   ```bash
   sudo yum install java-11-openjdk-devel
   sudo alternatives --config java
   sudo rpm --import https://pkg.jenkins.io/redhat/jenkins.io.key
   sudo yum install jenkins
   ```
2. Start and enable the service:
   ```bash
   sudo systemctl start jenkins
   sudo systemctl enable jenkins
   systemctl status jenkins.service
   journalctl -u jenkins.service
   ```
3. Retrieve the initial admin password:
   ```bash
   cat /var/lib/jenkins/secrets/initialAdminPassword
   ```
4. Log in to the Jenkins web UI, install the required plugins, and set up the admin user/password.
   > Jenkins replaces the previously used Capistrano-based deployment process.

---

## Phase 7: Post-Setup Verification

Run these checks to confirm the environment is correctly configured before going live:

```bash
# As the deployment user
ruby -v          # expect: ruby 3.3.0
rails -v         # expect: Rails 6.1.7

# As root
nginx -t          # expect: syntax ok, test successful
systemctl status postgresql-13   # expect: active (running)

# Confirm secure file permissions
ls -ltrh /var/lib/pgsql/13/data/pg_hba.conf   # expect: -rw------- postgres:postgres
ls -ltrh /etc/nginx/conf.d/aisle.conf         # expect: -rw-r--r-- root:root

# Confirm Passenger is wired into the vhost config
cat /etc/nginx/conf.d/aisle.conf | grep "passenger"
```

If all checks pass, the VM is ready to serve the application.

---

## Summary Checklist

- [ ] VM created, SELinux disabled, OS updated
- [ ] `dnf-automatic` security updates enabled
- [ ] Non-root sudo user + SSH key created and tested
- [ ] Cloudflare IPs whitelisted via iptables
- [ ] Dev tools, EPEL, PowerTools, and Ruby build deps installed
- [ ] File descriptor limits raised
- [ ] SSH hardened (no root login, no password auth, no X11 forwarding)
- [ ] Nginx + Passenger installed and configured
- [ ] rbenv + Ruby 3.3.0 + Rails 6.1.7 installed under `deployment` user
- [ ] Nginx security headers and vhost configured
- [ ] PostgreSQL 13 installed, tuned, and SSL-enabled
- [ ] Deployment directory ownership and Postgres client cert set
- [ ] Jenkins installed, configured, and validated
- [ ] Post-setup verification checks all pass
