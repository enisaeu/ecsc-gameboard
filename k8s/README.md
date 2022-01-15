# How to bring up ecsc-gameboard on AWS

We will use kybespray to bring up the necessary infrastructure on AWS.
First you will need to setup your local environment with the required packages.

We will use "asdf" <https://asdf-vm.com/> to install the necessary packages which is compatible with MacOS and Linux.

## Install prerequisites

```bash
   sudo apt install curl git 
   git clone https://github.com/asdf-vm/asdf.git ~/.asdf --branch v0.9.0
   echo ". $HOME/.asdf/asdf.sh" >>  ~/.bashrc
   echo ". $HOME/.asdf/completions/asdf.bash" >> ~/.bashrc
   source ~/.basrch
```

Afterwards will install the required package versions with asdf.

```bash
   cd ecsc-gameboard/k8s/
   asdf plugin add terraform
   asdf plugin add ansible-base
   asdf plugin add helm
   asdf plugin add kubectl
   asdf install
```

Install Taskfile.dev <https://taskfile.dev/>

```bash
  sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d
```

You can create a user through AWS console or use an existing one with the following permissions:

> AmazonEC2FullAccess  
> IAMFullAccess  
> ElasticLoadBalancingFullAccess  
> AmazonVPCFullAccess

Then we will need to edit the k8s.env file. Fill the necessary AWS values and save it as .env file.

## Bring up the infrastructure and kubernetes

Check the tasks that are available

```bash
$ task
task: [default] task -l
task: Available tasks for this project:
* clone-kubespray:              Clone the kubespray repo v2.17.1
* delete-all:                   CAUTION Delete everything.
* deploy-infrastructure:        Bring up the infrastructure on AWS.
* destroy-infrastructure:       Destroy everything.
* install-gameboard:            Install ECSC gameboard.
* install-monitoring:           Install monitoring tools.
* install-mysql:                Add bitnami helm repository.
* install-nginx-ingress:        Install nginx ingress.
* k8s-requirements:             Install k8s requirements.
* provision-infrastructure:     Bring up the infrastructure on AWS.
```

We will start by deploying the infrastructure:

```bash
$ task deploy-infrastructure -- --auto-aprove
task: Task "clone-kubespray" is up to date
task: [deploy-infrastructure] sed -i "s/aws_kube_worker_num       = 4/aws_kube_worker_num       = 1/g" kubespray/contrib/terraform/aws/terraform.tfvars
task: [deploy-infrastructure] terraform -chdir=kubespray/contrib/terraform/aws init
Initializing modules...
```

When finished with bringing up the infrastructure we will continue with deploy the Kubernetes cluster.

```bash
$ task install-k8s
task: [install-k8s] virtualenv kubespray
$ ...
```

When done check the status of the kubernetes nodes:

```bash
$ export KUBECONFIG=~/.kube/aws_config
$ kubectl get nodes
NAME                                           STATUS   ROLES                  AGE   VERSION
ip-10-250-192-232.eu-west-1.compute.internal   Ready    <none>                 20m   v1.21.6
ip-10-250-192-99.eu-west-1.compute.internal    Ready    control-plane,master   22m   v1.21.6
ip-10-250-201-61.eu-west-1.compute.internal    Ready    control-plane,master   23m   v1.21.6
ip-10-250-215-130.eu-west-1.compute.internal   Ready    control-plane,master   22m   v1.21.6
```

## Install the ECSC gameboard application

```bash
task install-gameboard
```

Verify that all pods are running and healthy:

```bash
$ kubectl get pods -A
NAMESPACE       NAME                                                                   READY   STATUS      RESTARTS   AGE
gameboard       gameboard-ccb78899-srb96                                               1/1     Running     0          86s
ingress-nginx   ingress-nginx-admission-create-czffg                                   0/1     Completed   0          111s
ingress-nginx   ingress-nginx-admission-patch-dq6n2                                    0/1     Completed   1          111s
ingress-nginx   ingress-nginx-controller-54bfb9bb-n6r45                                1/1     Running     0          113s
kube-system     calico-kube-controllers-8575b76f66-nfgb5                               1/1     Running     2          27m
kube-system     calico-node-49hnb                                                      1/1     Running     0          29m
kube-system     calico-node-67stg                                                      1/1     Running     0          29m
kube-system     calico-node-f2gx8                                                      1/1     Running     0          29m
kube-system     calico-node-mz5xm                                                      1/1     Running     0          29m
kube-system     coredns-8474476ff8-5sj9f                                               1/1     Running     0          26m
kube-system     coredns-8474476ff8-75whz                                               1/1     Running     0          25m
kube-system     dns-autoscaler-7df78bfcfb-2ghhn                                        1/1     Running     0          25m
kube-system     ebs-csi-controller-86cdb4bc89-fp897                                    4/4     Running     0          24m
kube-system     ebs-csi-node-62ns8                                                     3/3     Running     0          24m
kube-system     kube-apiserver-ip-10-250-192-99.eu-west-1.compute.internal             1/1     Running     0          33m
kube-system     kube-apiserver-ip-10-250-201-61.eu-west-1.compute.internal             1/1     Running     0          34m
kube-system     kube-apiserver-ip-10-250-215-130.eu-west-1.compute.internal            1/1     Running     0          33m
kube-system     kube-controller-manager-ip-10-250-192-99.eu-west-1.compute.internal    1/1     Running     1          33m
kube-system     kube-controller-manager-ip-10-250-201-61.eu-west-1.compute.internal    1/1     Running     1          34m
kube-system     kube-controller-manager-ip-10-250-215-130.eu-west-1.compute.internal   1/1     Running     1          33m
kube-system     kube-proxy-65n65                                                       1/1     Running     0          30m
kube-system     kube-proxy-c4cfg                                                       1/1     Running     0          30m
kube-system     kube-proxy-d6tdq                                                       1/1     Running     0          30m
kube-system     kube-proxy-hgb6g                                                       1/1     Running     0          30m
kube-system     kube-scheduler-ip-10-250-192-99.eu-west-1.compute.internal             1/1     Running     1          33m
kube-system     kube-scheduler-ip-10-250-201-61.eu-west-1.compute.internal             1/1     Running     1          34m
kube-system     kube-scheduler-ip-10-250-215-130.eu-west-1.compute.internal            1/1     Running     1          33m
kube-system     nginx-proxy-ip-10-250-192-232.eu-west-1.compute.internal               1/1     Running     0          31m
kube-system     nodelocaldns-cxfhb                                                     1/1     Running     0          25m
kube-system     nodelocaldns-glfnl                                                     1/1     Running     0          25m
kube-system     nodelocaldns-plbrm                                                     1/1     Running     0          25m
kube-system     nodelocaldns-qfw5r                                                     1/1     Running     0          25m
mysql           mysql-0                                                                1/1     Running     0          90s
```

Once deployed we should be able to get the LoadBalancer IP utilizing the following command:

```bash
$ kubectl get svc -n gameboard
NAME        TYPE           CLUSTER-IP      EXTERNAL-IP                                                              PORT(S)        AGE
gameboard   LoadBalancer   10.233.61.227   a719a30fe6d854031a335908b86fe975-155169405.eu-west-1.elb.amazonaws.com   80:32565/TCP   45s
```

You can now navigate to the ECSC platform using the external IP of the load balancer eg <http://a719a30fe6d854031a335908b86fe975-155169405.eu-west-1.elb.amazonaws.com/>

## Deploy Monitoring

We will use Prometheus/Grafana/NodeExporter for monitoring of the infrastructure and the pods.

```bash
task install-monitoring
```

Once installed check the status of the pods that are all running and healthy.

```bash
$ kubectl --namespace monitoring get pods -l "release=prometheus-stack"
NAME                                                   READY   STATUS    RESTARTS   AGE
prometheus-stack-kube-prom-operator-5d44fc7f67-smqtq   1/1     Running   0          60s
prometheus-stack-kube-state-metrics-7d7db94dc7-mrmr9   1/1     Running   0          60s
prometheus-stack-prometheus-node-exporter-7b5dz        1/1     Running   0          60s
prometheus-stack-prometheus-node-exporter-brlhk        1/1     Running   0          60s
prometheus-stack-prometheus-node-exporter-fs94c        1/1     Running   0          60s
prometheus-stack-prometheus-node-exporter-qpvkx        1/1     Running   0          60s
```

Get the grafana LoadBalancer IP

```bash
$ kubectl get svc/prometheus-stack-grafana -n monitoring
NAME                       TYPE           CLUSTER-IP      EXTERNAL-IP                                                               PORT(S)        AGE
prometheus-stack-grafana   LoadBalancer   10.233.32.153   a6ba0e0cb1bf64a5b9a19d89322371ba-1007100582.eu-west-1.elb.amazonaws.com   80:32429/TCP   12m
```

You can now navigate to the LoadBalancer IP and login to grafana with the default username:password admin:prom-operator.

From there you can navigate to the different dashboards on monitor node and pod status.
