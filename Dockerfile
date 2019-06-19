FROM alpine:3.6

RUN apk --update add --no-cache openssh bash \
    && sed -i s/#PermitRootLogin.*/PermitRootLogin\ yes/ /etc/ssh/sshd_config \
    && echo "root:root" | chpasswd

RUN sed -ie 's/#Port 22/Port 22/g' /etc/ssh/sshd_config
RUN sed -ie 's/#PermitUserEnvironment no/PermitUserEnvironment yes/g' /etc/ssh/sshd_config
RUN echo "AcceptEnv FOO" >> /etc/ssh/sshd_config
RUN sed -ri 's/#HostKey \/etc\/ssh\/ssh_host_key/HostKey \/etc\/ssh\/ssh_host_key/g' /etc/ssh/sshd_config
RUN sed -ir 's/#HostKey \/etc\/ssh\/ssh_host_rsa_key/HostKey \/etc\/ssh\/ssh_host_rsa_key/g' /etc/ssh/sshd_config
RUN sed -ir 's/#HostKey \/etc\/ssh\/ssh_host_dsa_key/HostKey \/etc\/ssh\/ssh_host_dsa_key/g' /etc/ssh/sshd_config
RUN sed -ir 's/#HostKey \/etc\/ssh\/ssh_host_ecdsa_key/HostKey \/etc\/ssh\/ssh_host_ecdsa_key/g' /etc/ssh/sshd_config
RUN sed -ir 's/#HostKey \/etc\/ssh\/ssh_host_ed25519_key/HostKey \/etc\/ssh\/ssh_host_ed25519_key/g' /etc/ssh/sshd_config
RUN echo "Ciphers aes128-ctr,aes192-ctr,aes256-ctr,aes128-cbc,aes192-cbc,aes256-cbc" >> /etc/ssh/sshd_config
# RUN echo "LogLevel DEBUG3" >> /etc/ssh/sshd_config

RUN /usr/bin/ssh-keygen -A
RUN ssh-keygen -t rsa -b 4096 -f  /etc/ssh/ssh_host_key

COPY tests/key_ecdsa.pub /tmp/key_ecdsa.pub
COPY tests/key_passphrase_rsa.pub /tmp/key_passphrase_rsa.pub
COPY tests/key_rsa.pub /tmp/key_rsa.pub

RUN mkdir /root/.ssh \
    && cat /tmp/key_ecdsa.pub > /root/.ssh/authorized_keys \
    && cat /tmp/key_passphrase_rsa.pub >> /root/.ssh/authorized_keys \
    && cat /tmp/key_rsa.pub >> /root/.ssh/authorized_keys

EXPOSE 22

CMD ["/usr/sbin/sshd","-De"]